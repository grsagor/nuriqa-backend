<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Size;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CheckoutShippingService;
use App\Services\PlatformFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PlatformFeeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function mockFlatShippingFee(float $fee = 15.0): void
    {
        $this->mock(CheckoutShippingService::class, function ($mock) use ($fee) {
            $mock->shouldReceive('quote')->andReturn([
                'total_fee' => $fee,
                'quote' => [
                    'currency' => 'GBP',
                    'total_fee' => $fee,
                    'seller_count' => 1,
                    'sellers' => [],
                ],
            ]);
            $mock->shouldReceive('shippingAddressFromCheckout')->andReturn([
                'name' => 'A B',
                'address_line_1' => '1 Test St',
                'city' => 'London',
                'postcode' => 'E1 6AN',
                'country' => 'GB',
            ]);
            $mock->shouldReceive('createShipmentsForTransaction')->andReturn([]);
        });
    }

    private function checkoutPayload(Cart $cart, int $quantity = 2): array
    {
        return [
            'billing_first_name' => 'A',
            'billing_last_name' => 'B',
            'billing_email' => 'a@a.com',
            'billing_phone' => '1',
            'shipping_address_line_1' => '1 Test Street',
            'shipping_city' => 'London',
            'shipping_postcode' => 'E1 6AN',
            'payment_method' => 'cod',
            'agree_terms' => true,
            'cart_items' => [
                ['id' => $cart->id, 'quantity' => $quantity],
            ],
        ];
    }

    public function test_checkout_applies_fixed_admin_fee_per_line(): void
    {
        PlatformSetting::query()->update([
            'fee_percentage' => 10,
            'admin_fee_amount' => 0.75,
        ]);
        PlatformFeeService::clearCache();
        $this->mockFlatShippingFee(15.0);

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $category = Category::query()->create(['name' => 'Test Cat']);
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

        $cart = Cart::query()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);
        $token = JWTAuth::fromUser($buyer);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/checkout', $this->checkoutPayload($cart, 2));

        $response->assertStatus(201);
        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(0.75, (float) $transaction->platform_fee_total);
        $this->assertEquals(0.75, (float) $transaction->admin_fee_total);
        $this->assertEquals(200.75, (float) $transaction->subtotal);
        $this->assertEquals(215.75, (float) $transaction->total);
    }

    public function test_checkout_applies_admin_fee_for_free_items(): void
    {
        PlatformSetting::query()->update(['admin_fee_amount' => 0.75]);
        PlatformFeeService::clearCache();
        $this->mockFlatShippingFee(15.0);

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $category = Category::query()->create(['name' => 'Test Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        $product = Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Free item',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'new',
            'price' => 0.00,
            'is_free' => true,
            'contribution_mode' => 'flexible',
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 5,
            'type' => 'seller',
            'active_listing' => true,
        ]);

        $cart = Cart::query()->create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 3]);
        $token = JWTAuth::fromUser($buyer);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders/checkout', $this->checkoutPayload($cart, 3));

        $response->assertStatus(201);
        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(0.75, (float) $transaction->platform_fee_total);
        $this->assertEquals(0.75, (float) $transaction->subtotal);
        $this->assertEquals(15.75, (float) $transaction->total);
    }

    public function test_admin_fee_constant_is_seventy_five_pence(): void
    {
        $this->assertSame(0.75, PlatformFeeService::DEFAULT_ADMIN_FEE_AMOUNT);
        $this->assertSame(0.75, PlatformFeeService::MIN_LINE_BUYER_PROTECTION_AMOUNT);
    }
}
