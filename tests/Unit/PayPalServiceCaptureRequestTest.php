<?php

namespace Tests\Unit;

use App\Services\PayPalService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalServiceCaptureRequestTest extends TestCase
{
    public function test_capture_order_posts_json_object_body(): void
    {
        config([
            'services.paypal.base_url' => 'https://api-m.sandbox.paypal.com',
            'services.paypal.client_id' => 'test-client-id',
            'services.paypal.client_secret' => 'test-secret',
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'fake-access-token',
                'token_type' => 'Bearer',
            ], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/*/capture' => Http::response([
                'id' => 'ORDER',
                'status' => 'COMPLETED',
                'payer' => [
                    'payer_id' => 'PAYER',
                    'email_address' => 'buyer@example.com',
                ],
                'purchase_units' => [
                    [
                        'payments' => [
                            'captures' => [
                                [
                                    'id' => 'CAPTURE',
                                    'status' => 'COMPLETED',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 201),
        ]);

        $service = $this->app->make(PayPalService::class);
        $result = $service->captureOrder('PAYPAL-ORDER-ID');

        $this->assertSame('COMPLETED', strtoupper((string) ($result['status'] ?? '')));

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-ID/capture') {
                return false;
            }

            if ($request->body() !== '{}') {
                return false;
            }

            return str_contains($request->header('Content-Type')[0] ?? '', 'application/json')
                && ($request->header('Prefer')[0] ?? '') === 'return=representation';
        });
    }
}
