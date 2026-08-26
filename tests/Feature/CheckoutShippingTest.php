<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\Size;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PostcodeDistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CheckoutShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_uses_distance_based_delivery_fee_and_creates_pending_shipment(): void
    {
        \App\Models\PlatformSetting::query()->update(['fee_percentage' => 0]);

        $seller = User::factory()->create([
            'postal_code' => 'SW1A 1AA',
            'address' => '10 Downing Street',
            'city' => 'London',
        ]);
        $buyer = User::factory()->create();
        $category = Category::query()->create(['name' => 'Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        $product = Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Item',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'new',
            'price' => 100.00,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 5,
            'type' => 'seller',
            'active_listing' => true,
        ]);

        $cart = Cart::query()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->mock(PostcodeDistanceService::class, function ($mock) {
            $mock->shouldReceive('normalize')->andReturnUsing(
                fn (string $p) => strtoupper(preg_replace('/\s+/', '', $p) ?? '')
            );
            $mock->shouldReceive('format')->andReturnUsing(fn (string $p) => strtoupper($p));
            $mock->shouldReceive('distanceMiles')->andReturn([
                'distance_miles' => 15.0,
                'from' => ['postcode' => 'SW1A 1AA', 'latitude' => 51.5, 'longitude' => -0.1],
                'to' => ['postcode' => 'E1 6AN', 'latitude' => 51.52, 'longitude' => -0.07],
            ]);
        });

        config([
            'services.evri.auth_token_url' => '',
            'services.evri.auth_id' => '',
            'services.evri.auth_secret' => '',
            'shipping.distance_bands' => [
                ['max_miles' => 20, 'fee' => 3.99],
                ['max_miles' => null, 'fee' => 9.99],
            ],
        ]);

        $token = JWTAuth::fromUser($buyer);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/checkout', [
                'billing_first_name' => 'A',
                'billing_last_name' => 'B',
                'billing_email' => 'a@a.com',
                'billing_phone' => '1',
                'shipping_address_line_1' => '1 Test Street',
                'shipping_city' => 'London',
                'shipping_postcode' => 'E1 6AN',
                'shipping_country' => 'GB',
                'payment_method' => 'cod',
                'agree_terms' => true,
                'cart_items' => [
                    ['id' => $cart->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);

        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(3.99, (float) $transaction->delivery_fee);
        $this->assertEquals(104.99, (float) $transaction->total);

        $shipment = Shipment::query()->where('transaction_id', $transaction->id)->first();
        $this->assertNotNull($shipment);
        $this->assertSame($seller->id, $shipment->seller_id);
        $this->assertSame('pending', $shipment->status);
        $this->assertEquals(3.99, (float) $shipment->shipping_fee);
    }

    public function test_seller_can_update_shipment_status(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $transaction = Transaction::query()->create([
            'user_id' => $buyer->id,
            'invoice_no' => 'INV-TEST-1',
            'status' => 'pending',
            'subtotal' => 10,
            'tax' => 0,
            'delivery_fee' => 3.99,
            'coupon_discount' => 0,
            'total' => 13.99,
            'billing_first_name' => 'A',
            'billing_last_name' => 'B',
            'billing_email' => 'a@a.com',
            'billing_phone' => '1',
            'payment_method' => 'cod',
        ]);

        $shipment = Shipment::query()->create([
            'transaction_id' => $transaction->id,
            'seller_id' => $seller->id,
            'carrier' => 'evri',
            'tracking_number' => 'H01TEST',
            'status' => 'created',
            'shipping_fee' => 3.99,
        ]);

        $token = JWTAuth::fromUser($seller);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/evri/shipments/'.$shipment->id, [
                'status' => 'in_transit',
            ])
            ->assertOk()
            ->assertJsonPath('shipment.status', 'in_transit');
    }
}
