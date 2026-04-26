<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Size;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PlatformFeeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_applies_platform_fee_to_subtotal_and_total(): void
    {
        PlatformSetting::query()->update(['fee_percentage' => 10]);

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
            ->postJson('/api/v1/orders/checkout', [
                'billing_first_name' => 'A',
                'billing_last_name' => 'B',
                'billing_email' => 'a@a.com',
                'billing_phone' => '1',
                'payment_method' => 'cod',
                'agree_terms' => true,
                'cart_items' => [
                    ['id' => $cart->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201);
        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(20.0, (float) $transaction->platform_fee_total);
        $this->assertEquals(220.0, (float) $transaction->subtotal);
        $this->assertEquals(235.0, (float) $transaction->total);
    }
}
