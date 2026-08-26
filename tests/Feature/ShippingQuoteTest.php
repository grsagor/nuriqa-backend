<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Services\PostcodeDistanceService;
use App\Services\ShippingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ShippingQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_for_distance_uses_configured_bands(): void
    {
        config([
            'shipping.distance_bands' => [
                ['max_miles' => 20, 'fee' => 3.99],
                ['max_miles' => 50, 'fee' => 5.49],
                ['max_miles' => null, 'fee' => 9.99],
            ],
        ]);

        $service = app(ShippingQuoteService::class);

        $this->assertSame([3.99, '0_20'], $service->feeForDistance(12.5));
        $this->assertSame([5.49, 'upto_50'], $service->feeForDistance(40));
        $this->assertSame([9.99, '200_plus'], $service->feeForDistance(250));
    }

    public function test_checkout_rates_sums_one_fee_per_seller(): void
    {
        $sellerA = User::factory()->create([
            'postal_code' => 'SW1A 1AA',
            'name' => 'Seller A',
        ]);
        $sellerB = User::factory()->create([
            'postal_code' => 'M1 1AE',
            'name' => 'Seller B',
        ]);
        $buyer = User::factory()->create();

        $category = Category::query()->create(['name' => 'Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        $productA = Product::query()->create([
            'owner_id' => $sellerA->id,
            'title' => 'Item A',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'new',
            'price' => 10,
            'is_free' => false,
            'stock' => 5,
            'type' => 'seller',
            'active_listing' => true,
        ]);
        $productB = Product::query()->create([
            'owner_id' => $sellerB->id,
            'title' => 'Item B',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'new',
            'price' => 20,
            'is_free' => false,
            'stock' => 5,
            'type' => 'seller',
            'active_listing' => true,
        ]);

        $cartA = Cart::query()->create(['user_id' => $buyer->id, 'product_id' => $productA->id, 'quantity' => 1]);
        $cartB = Cart::query()->create(['user_id' => $buyer->id, 'product_id' => $productB->id, 'quantity' => 1]);

        $this->mock(PostcodeDistanceService::class, function ($mock) {
            $mock->shouldReceive('normalize')->andReturnUsing(
                fn (string $p) => strtoupper(preg_replace('/\s+/', '', $p) ?? '')
            );
            $mock->shouldReceive('format')->andReturnUsing(function (string $p) {
                $c = strtoupper(preg_replace('/\s+/', '', $p) ?? '');

                return strlen($c) >= 5 ? substr($c, 0, -3).' '.substr($c, -3) : $c;
            });
            $mock->shouldReceive('distanceMiles')
                ->andReturn(
                    [
                        'distance_miles' => 10.0,
                        'from' => ['postcode' => 'SW1A 1AA', 'latitude' => 51.5, 'longitude' => -0.1],
                        'to' => ['postcode' => 'E1 6AN', 'latitude' => 51.52, 'longitude' => -0.07],
                    ],
                    [
                        'distance_miles' => 180.0,
                        'from' => ['postcode' => 'M1 1AE', 'latitude' => 53.48, 'longitude' => -2.24],
                        'to' => ['postcode' => 'E1 6AN', 'latitude' => 51.52, 'longitude' => -0.07],
                    ],
                );
        });

        config([
            'shipping.distance_bands' => [
                ['max_miles' => 20, 'fee' => 3.99],
                ['max_miles' => 200, 'fee' => 8.49],
                ['max_miles' => null, 'fee' => 9.99],
            ],
        ]);

        $token = JWTAuth::fromUser($buyer);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/evri/checkout-rates', [
                'shipping_postcode' => 'E1 6AN',
                'cart_item_ids' => [$cartA->id, $cartB->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.seller_count', 2)
            ->assertJsonPath('data.total_fee', 12.48);
    }

    public function test_fallback_fee_when_seller_has_no_postcode(): void
    {
        config(['shipping.fallback_fee' => 7.99]);

        $seller = User::factory()->make([
            'id' => 99,
            'name' => 'No Postcode Seller',
            'postal_code' => null,
        ]);

        $quote = app(ShippingQuoteService::class)->quoteForSeller($seller, 'E16AN');

        $this->assertSame(7.99, $quote['fee']);
        $this->assertSame('fallback', $quote['band']);
        $this->assertNull($quote['distance_miles']);
    }
}
