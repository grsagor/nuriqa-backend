<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceOffer;
use App\Models\Size;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ProductPriceOfferTest extends TestCase
{
    use RefreshDatabase;

    private function seedPaidProduct(User $seller): Product
    {
        $category = Category::query()->create(['name' => 'Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        return Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Jacket',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'new',
            'price' => 300.00,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 3,
            'type' => 'seller',
            'active_listing' => true,
        ]);
    }

    public function test_buyer_can_submit_offer_below_list_price(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->seedPaidProduct($seller);

        $token = JWTAuth::fromUser($buyer);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/product-price-offers', [
                'product_id' => $product->id,
                'offered_unit_price' => 200,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_price_offers', [
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'offered_unit_price' => 200,
            'status' => ProductPriceOffer::STATUS_PENDING,
        ]);
    }

    public function test_seller_can_approve_and_buyer_checkouts_at_offer_price(): void
    {
        \App\Models\PlatformSetting::query()->update(['fee_percentage' => 10]);

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->seedPaidProduct($seller);

        $offer = ProductPriceOffer::query()->create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'offered_unit_price' => 200.00,
            'status' => ProductPriceOffer::STATUS_PENDING,
        ]);

        $sellerToken = JWTAuth::fromUser($seller);
        $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->postJson("/api/v1/product-price-offers/{$offer->id}/approve")
            ->assertOk()
            ->assertJsonPath('success', true);

        $offer->refresh();
        $this->assertSame(ProductPriceOffer::STATUS_APPROVED, $offer->status);

        $cart = Cart::query()->create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $buyerToken = JWTAuth::fromUser($buyer);
        $this->withHeader('Authorization', 'Bearer '.$buyerToken)
            ->postJson('/api/v1/orders/checkout', [
                'billing_first_name' => 'A',
                'billing_last_name' => 'B',
                'billing_email' => 'a@a.com',
                'billing_phone' => '1',
                'payment_method' => 'cod',
                'agree_terms' => true,
                'cart_items' => [
                    ['id' => $cart->id, 'quantity' => 1],
                ],
            ])
            ->assertStatus(201);

        $line = \App\Models\TransactionSellLine::query()->first();
        $this->assertSame(200.0, (float) $line->unit_price);
        $this->assertSame(200.0, (float) $line->subtotal);

        $offer->refresh();
        $this->assertSame(ProductPriceOffer::STATUS_CONSUMED, $offer->status);
        $this->assertNotNull($offer->consumed_at);
        $this->assertSame(Transaction::query()->first()->id, $offer->transaction_id);
    }

    public function test_buyer_is_notified_when_offer_is_approved(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->seedPaidProduct($seller);

        $offer = ProductPriceOffer::query()->create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'offered_unit_price' => 200.00,
            'status' => ProductPriceOffer::STATUS_PENDING,
        ]);

        $sellerToken = JWTAuth::fromUser($seller);
        $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->postJson("/api/v1/product-price-offers/{$offer->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('seller_notifications', [
            'user_id' => $buyer->id,
            'type' => 'price_offer_response',
            'entity_id' => $offer->id,
            'title' => 'Offer approved',
        ]);
    }

    public function test_buyer_is_notified_when_offer_is_declined(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $product = $this->seedPaidProduct($seller);

        $offer = ProductPriceOffer::query()->create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'offered_unit_price' => 200.00,
            'status' => ProductPriceOffer::STATUS_PENDING,
        ]);

        $sellerToken = JWTAuth::fromUser($seller);
        $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->postJson("/api/v1/product-price-offers/{$offer->id}/decline")
            ->assertOk();

        $this->assertDatabaseHas('seller_notifications', [
            'user_id' => $buyer->id,
            'type' => 'price_offer_response',
            'entity_id' => $offer->id,
            'title' => 'Offer declined',
        ]);
    }
}
