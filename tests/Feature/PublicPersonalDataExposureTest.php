<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\SponsorRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PublicPersonalDataExposureTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Size $size;

    private User $owner;

    private User $requester;

    private Product $product;

    private SponsorRequest $sponsorRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::query()->create(['name' => 'Cat']);
        $this->size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        $this->owner = User::factory()->create([
            'name' => 'Public Seller',
            'email' => 'seller-pii@example.com',
            'phone' => '+441111111111',
            'address' => '1 Secret Street',
            'apartment' => 'Flat 2',
            'city' => 'London',
            'postal_code' => 'SW1A 1AA',
            'notification_settings' => ['emailNotifications' => true],
            'firebase_uid' => 'firebase-secret-owner',
            'otp' => '999999',
        ]);

        $this->requester = User::factory()->create([
            'name' => 'Public Requester',
            'email' => 'requester-pii@example.com',
            'phone' => '+442222222222',
            'address' => '9 Hidden Lane',
            'apartment' => 'Unit 4',
            'city' => 'Manchester',
            'postal_code' => 'M1 1AE',
            'notification_settings' => ['newOrderRequest' => true],
            'firebase_uid' => 'firebase-secret-requester',
            'otp' => '888888',
        ]);

        $this->product = Product::query()->create([
            'owner_id' => $this->owner->id,
            'title' => 'Sponsored Coat',
            'description' => 'Warm coat',
            'size_id' => $this->size->id,
            'category_id' => $this->category->id,
            'condition' => 'new',
            'price' => 25,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 1,
            'type' => 'seller',
            'active_listing' => true,
        ]);

        $this->sponsorRequest = SponsorRequest::query()->create([
            'user_id' => $this->requester->id,
            'product_id' => $this->product->id,
            'request_reason' => 'Need warm clothes for winter',
            'first_name' => 'Private',
            'last_name' => 'Person',
            'email' => 'sponsor-form@example.com',
            'phone' => '+443333333333',
            'address' => '12 Delivery Road',
            'apartment' => 'Apt 5',
            'city' => 'Birmingham',
            'postal_code' => 'B1 1BB',
            'additional_info' => 'Leave with neighbour',
            'keep_updated' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * @return list<string>
     */
    private function forbiddenPiiFragments(): array
    {
        return [
            'seller-pii@example.com',
            'requester-pii@example.com',
            'sponsor-form@example.com',
            '+441111111111',
            '+442222222222',
            '+443333333333',
            '1 Secret Street',
            '9 Hidden Lane',
            '12 Delivery Road',
            'SW1A 1AA',
            'M1 1AE',
            'B1 1BB',
            'firebase-secret-owner',
            'firebase-secret-requester',
            '999999',
            '888888',
            'Leave with neighbour',
            'Flat 2',
            'Unit 4',
            'Apt 5',
        ];
    }

    public function test_public_product_details_omit_owner_personal_data(): void
    {
        $response = $this->getJson('/api/v1/products/details/'.$this->product->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.owner.name', 'Public Seller')
            ->assertJsonPath('data.owner.id', $this->owner->id)
            ->assertJsonMissingPath('data.owner.email')
            ->assertJsonMissingPath('data.owner.phone')
            ->assertJsonMissingPath('data.owner.address')
            ->assertJsonMissingPath('data.owner.postal_code')
            ->assertJsonMissingPath('data.owner.notification_settings')
            ->assertJsonMissingPath('data.owner.firebase_uid')
            ->assertJsonMissingPath('data.owner.otp');

        $payload = $response->getContent();
        foreach ($this->forbiddenPiiFragments() as $fragment) {
            $this->assertStringNotContainsString($fragment, $payload);
        }
    }

    public function test_public_sponsor_request_index_omits_personal_data(): void
    {
        $response = $this->getJson('/api/v1/sponsor-requests/');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $this->sponsorRequest->id)
            ->assertJsonPath('data.0.user.name', 'Public Requester')
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.phone')
            ->assertJsonMissingPath('data.0.address')
            ->assertJsonMissingPath('data.0.postal_code')
            ->assertJsonMissingPath('data.0.first_name')
            ->assertJsonMissingPath('data.0.last_name')
            ->assertJsonMissingPath('data.0.additional_info')
            ->assertJsonMissingPath('data.0.keep_updated')
            ->assertJsonMissingPath('data.0.user.email')
            ->assertJsonMissingPath('data.0.product.owner.email');

        $payload = $response->getContent();
        foreach ($this->forbiddenPiiFragments() as $fragment) {
            $this->assertStringNotContainsString($fragment, $payload);
        }
    }

    public function test_public_sponsor_request_show_omits_personal_data(): void
    {
        $response = $this->getJson('/api/v1/sponsor-requests/'.$this->sponsorRequest->id.'/public');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->sponsorRequest->id)
            ->assertJsonPath('data.request_reason', 'Need warm clothes for winter')
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.address')
            ->assertJsonMissingPath('data.postal_code')
            ->assertJsonMissingPath('data.first_name')
            ->assertJsonMissingPath('data.last_name')
            ->assertJsonMissingPath('data.additional_info')
            ->assertJsonMissingPath('data.user.email')
            ->assertJsonMissingPath('data.product.owner.phone');

        $payload = $response->getContent();
        foreach ($this->forbiddenPiiFragments() as $fragment) {
            $this->assertStringNotContainsString($fragment, $payload);
        }
    }

    public function test_authenticated_profile_still_returns_private_fields(): void
    {
        $token = JWTAuth::fromUser($this->requester);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/my-user-info');

        $response->assertOk()
            ->assertJsonPath('data.email', 'requester-pii@example.com')
            ->assertJsonPath('data.phone', '+442222222222')
            ->assertJsonPath('data.address', '9 Hidden Lane')
            ->assertJsonPath('data.postal_code', 'M1 1AE');
    }
}
