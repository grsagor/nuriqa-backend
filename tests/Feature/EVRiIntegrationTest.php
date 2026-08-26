<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class EVRiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.evri.auth_token_url' => 'https://auth.example.test/connect/token',
            'services.evri.base_url' => 'https://api.example.test',
            'services.evri.audience' => 'https://api.example.test',
            'services.evri.auth_id' => 'test-auth-id',
            'services.evri.auth_secret' => 'test-auth-secret',
            'services.evri.oauth_client_id' => 'test-auth-id',
            'services.evri.oauth_client_secret' => 'test-auth-secret',
            'services.evri.api_key' => 'test-api-key',
            'services.evri.storage_disk' => 'public',
            'services.evri.webhook_secret' => '',
        ]);
    }

    public function test_authenticate_exchanges_credentials_and_checks_api_access(): void
    {
        $this->fakeSuccessfulEvriAuth();

        $this->getJson('/api/v1/evri/authenticate')
            ->assertOk()
            ->assertJsonPath('message', 'EVRi authentication successful')
            ->assertJsonPath('expires_in', 3600);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://auth.example.test/connect/token') {
                return false;
            }

            $this->assertSame('test-api-key', $request->header('apikey')[0] ?? null);
            $this->assertSame('client_credentials', $request['grant_type']);
            $this->assertSame('test-auth-id', $request['client_id']);
            $this->assertSame('test-auth-secret', $request['client_secret']);
            $this->assertSame('https://api.example.test', $request['audience']);

            return true;
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.example.test/api/customers'
                && ($request->header('Authorization')[0] ?? '') === 'Bearer fake-evri-token'
                && ($request->header('apikey')[0] ?? '') === 'test-api-key';
        });
    }

    public function test_authenticate_fails_when_token_request_is_rejected(): void
    {
        Http::fake([
            'https://auth.example.test/connect/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $this->getJson('/api/v1/evri/authenticate')
            ->assertStatus(500)
            ->assertJsonFragment(['message' => 'EVRi authentication failed: {"error":"invalid_client"}']);
    }

    public function test_authenticate_fails_when_credentials_are_missing(): void
    {
        config([
            'services.evri.auth_token_url' => '',
            'services.evri.auth_id' => '',
            'services.evri.auth_secret' => '',
        ]);

        $this->getJson('/api/v1/evri/authenticate')
            ->assertStatus(500)
            ->assertJsonPath(
                'message',
                'EVRi is not configured. Set EVRI_AUTH_TOKEN_URL, EVRI_BASE_URL, EVRI_AUTH_ID, and EVRI_AUTH_SECRET.'
            );
    }

    public function test_validate_address_accepts_uk_postcode(): void
    {
        $this->postJson('/api/v1/evri/validate-address', [
            'name' => 'Jane Buyer',
            'address_line_1' => '10 Downing Street',
            'city' => 'London',
            'postcode' => 'SW1A1AA',
            'country' => 'GB',
        ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('formatted_address', 'Jane Buyer, 10 Downing Street, London, SW1A 1AA, GB');
    }

    public function test_validate_address_rejects_invalid_postcode(): void
    {
        $this->postJson('/api/v1/evri/validate-address', [
            'name' => 'Jane Buyer',
            'address_line_1' => '10 Downing Street',
            'city' => 'London',
            'postcode' => 'INVALID',
            'country' => 'GB',
        ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('formatted_address', null);
    }

    public function test_get_rates_returns_distance_based_buyer_fee(): void
    {
        $this->mock(\App\Services\PostcodeDistanceService::class, function ($mock) {
            $mock->shouldReceive('distanceMiles')->once()->andReturn([
                'distance_miles' => 12.0,
                'from' => ['postcode' => 'SW1A 1AA', 'latitude' => 51.5, 'longitude' => -0.1],
                'to' => ['postcode' => 'E1 1AA', 'latitude' => 51.52, 'longitude' => -0.07],
            ]);
        });

        config([
            'shipping.distance_bands' => [
                ['max_miles' => 20, 'fee' => 3.99],
                ['max_miles' => null, 'fee' => 9.99],
            ],
        ]);

        $this->getJson('/api/v1/evri/rates?from_postcode=SW1A1AA&to_postcode=E11AA&weight_g=500&length_cm=20&width_cm=15&height_cm=2')
            ->assertOk()
            ->assertJsonPath('rates.0.service', 'POSTABLE')
            ->assertJsonPath('rates.0.charged_to', 'buyer')
            ->assertJsonPath('rates.0.amount', 3.99);
    }

    public function test_guest_cannot_create_label(): void
    {
        $transaction = $this->createTransaction();

        $this->postJson('/api/v1/evri/transactions/'.$transaction->id.'/create-label', $this->labelPayload())
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_label(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://auth.example.test/connect/token' => Http::response([
                'access_token' => 'fake-evri-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.example.test/api/customers' => Http::response(['id' => 'customer-1'], 200),
            'https://api.example.test/api/parcels' => Http::response([
                'parcelSummaries' => [
                    [
                        'clientUID' => 'nuriqa-1',
                        'barcode' => 'H01TESTBARCODE',
                        'status' => 'CREATED',
                    ],
                ],
            ], 200),
            'https://api.example.test/api/labels/*' => Http::response('%PDF-1.4 fake-label', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $user = User::factory()->create();
        $transaction = $this->createTransaction($user);
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/evri/transactions/'.$transaction->id.'/create-label', $this->labelPayload())
            ->assertCreated()
            ->assertJsonPath('tracking_number', 'H01TESTBARCODE');

        $this->assertDatabaseHas('shipments', [
            'transaction_id' => $transaction->id,
            'carrier' => 'evri',
            'tracking_number' => 'H01TESTBARCODE',
            'status' => 'created',
        ]);

        Storage::disk('public')->assertExists('labels/transaction-'.$transaction->id.'/H01TESTBARCODE.pdf');

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://api.example.test/api/parcels') {
                return false;
            }

            $parcel = $request['parcels'][0] ?? [];

            return ($parcel['deliveryDetails']['deliveryAddress']['postcode'] ?? null) === 'E1 1AA'
                && ($parcel['deliveryDetails']['firstName'] ?? null) === 'Jane'
                && ($parcel['parcelDetails']['type'] ?? null) === 'STANDARD';
        });
    }

    public function test_create_label_fails_when_parcel_is_invalid(): void
    {
        $this->fakeSuccessfulEvriAuth();

        Http::fake([
            'https://auth.example.test/connect/token' => Http::response([
                'access_token' => 'fake-evri-token',
                'expires_in' => 3600,
            ], 200),
            'https://api.example.test/api/customers' => Http::response(['id' => 'customer-1'], 200),
            'https://api.example.test/api/parcels' => Http::response([
                'parcelSummaries' => [
                    [
                        'clientUID' => 'nuriqa-1',
                        'barcode' => null,
                        'status' => 'INVALID',
                        'errors' => [
                            ['error' => 'invalid_property', 'error_description' => 'Postcode is invalid'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $transaction = $this->createTransaction($user);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/evri/transactions/'.$transaction->id.'/create-label', $this->labelPayload())
            ->assertStatus(500);

        $this->assertStringContainsString('Postcode is invalid', (string) $response->json('message'));

        $this->assertDatabaseCount('shipments', 0);
    }

    public function test_tracking_returns_public_evri_url(): void
    {
        $user = User::factory()->create();
        $transaction = $this->createTransaction($user);
        $shipment = Shipment::query()->create([
            'transaction_id' => $transaction->id,
            'carrier' => 'evri',
            'tracking_number' => 'H01TRACKME',
            'status' => 'created',
        ]);
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/evri/shipments/'.$shipment->id.'/tracking')
            ->assertOk()
            ->assertJsonPath('tracking_data.tracking_number', 'H01TRACKME')
            ->assertJsonPath('tracking_data.tracking_url', 'https://www.evri.com/track-a-parcel/H01TRACKME');
    }

    private function fakeSuccessfulEvriAuth(): void
    {
        Http::fake([
            'https://auth.example.test/connect/token' => Http::response([
                'access_token' => 'fake-evri-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.example.test/api/customers' => Http::response(['id' => 'customer-1'], 200),
        ]);
    }

    private function createTransaction(?User $user = null): Transaction
    {
        $user ??= User::factory()->create();

        return Transaction::query()->create([
            'user_id' => $user->id,
            'invoice_no' => 'INV-EVRI-'.$user->id,
            'status' => 'processing',
            'subtotal' => 20,
            'total' => 25.50,
            'billing_first_name' => 'Jane',
            'billing_last_name' => 'Buyer',
            'billing_email' => 'jane@example.com',
            'billing_phone' => '07123456789',
            'payment_method' => 'cod',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function labelPayload(): array
    {
        return [
            'address_to' => [
                'name' => 'Jane Buyer',
                'address_line_1' => '1 Test Street',
                'city' => 'London',
                'postcode' => 'E1 1AA',
                'country' => 'GB',
                'phone' => '07123456789',
                'email' => 'jane@example.com',
            ],
            'address_from' => [
                'name' => 'Nuriqa Warehouse',
                'address_line_1' => '12 Sender Road',
                'city' => 'Manchester',
                'postcode' => 'M1 1AE',
                'country' => 'GB',
            ],
            'package_details' => [
                'weight_g' => 1500,
                'length_cm' => 30,
                'width_cm' => 20,
                'height_cm' => 10,
            ],
        ];
    }
}
