<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Size;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Models\User;
use App\Services\CheckoutShippingService;
use App\Services\PlatformFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class BuyerChosenPriceTest extends TestCase
{
    use RefreshDatabase;

    private function mockFlatShippingFee(float $fee = 15.0): void
    {
        $this->mock(CheckoutShippingService::class, function ($mock) use ($fee) {
            $mock->shouldReceive('quote')->andReturn([
                'total_fee' => $fee,
                'quote' => ['currency' => 'GBP', 'total_fee' => $fee, 'seller_count' => 1, 'sellers' => []],
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

    private function product(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'owner_id' => User::factory()->create()->id,
            'title' => 'Embroidered mint suit',
            'description' => 'd',
            'size_id' => Size::query()->firstOrCreate(['name' => 'M', 'type' => 'general'])->id,
            'category_id' => Category::query()->firstOrCreate(['name' => 'Test Cat'])->id,
            'condition' => 'new',
            'price' => 0,
            'guide_value' => 100,
            'is_free' => true,
            'contribution_mode' => Product::CONTRIBUTION_FLEXIBLE,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 1,
            'type' => 'hajra',
            'active_listing' => true,
        ], $overrides));
    }

    private function asBuyer(User $buyer): self
    {
        return $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($buyer));
    }

    public function test_buyer_can_add_a_hajra_garment_with_their_own_price(): void
    {
        PlatformSetting::query()->update(['admin_fee_amount' => 0.75]);
        PlatformFeeService::clearCache();
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->asBuyer($buyer)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 12.5])
            ->assertCreated();

        $this->assertEquals(12.5, (float) Cart::query()->first()->contribution_amount);

        $this->asBuyer($buyer)
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.0.product.checkout_seller_unit_price', 12.5)
            ->assertJsonPath('data.0.product.checkout_buyer_unit_price', 13.25)
            ->assertJsonPath('data.0.product.checkout_contribution_amount', 12.5);
    }

    public function test_contribution_below_the_minimum_is_rejected(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->asBuyer($buyer)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 0.5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('contribution_amount');
    }

    public function test_fixed_price_products_do_not_accept_a_custom_price(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product([
            'type' => 'seller',
            'price' => 40,
            'is_free' => false,
            'contribution_mode' => Product::CONTRIBUTION_FIXED,
        ]);

        $this->asBuyer($buyer)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('contribution_amount');
    }

    public function test_a_priced_hajra_garment_still_takes_a_custom_price(): void
    {
        PlatformSetting::query()->update(['admin_fee_amount' => 0.75]);
        PlatformFeeService::clearCache();
        $buyer = User::factory()->create();
        $product = $this->product(['price' => 100, 'is_free' => false, 'contribution_mode' => Product::CONTRIBUTION_FIXED]);

        $this->asBuyer($buyer)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 20])
            ->assertCreated();

        $this->asBuyer($buyer)
            ->getJson('/api/v1/cart')
            ->assertJsonPath('data.0.product.checkout_seller_unit_price', 20);
    }

    public function test_resubmitting_changes_the_amount_without_adding_another_unit(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product(['stock' => 1]);

        $this->asBuyer($buyer)->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 5])->assertCreated();
        $this->asBuyer($buyer)->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 9])->assertOk();

        $cart = Cart::query()->sole();
        $this->assertSame(1, $cart->quantity);
        $this->assertEquals(9.0, (float) $cart->contribution_amount);
    }

    public function test_checkout_charges_the_chosen_price_plus_admin_fee_and_delivery(): void
    {
        PlatformSetting::query()->update(['admin_fee_amount' => 0.75]);
        PlatformFeeService::clearCache();
        $this->mockFlatShippingFee(15.0);
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->asBuyer($buyer)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'contribution_amount' => 12.5])
            ->assertCreated();
        $cart = Cart::query()->first();

        $this->asBuyer($buyer)->postJson('/api/v1/orders/checkout', [
            'billing_first_name' => 'A',
            'billing_last_name' => 'B',
            'billing_email' => 'a@a.com',
            'billing_phone' => '1',
            'shipping_address_line_1' => '1 Test Street',
            'shipping_city' => 'London',
            'shipping_postcode' => 'E1 6AN',
            'payment_method' => 'cod',
            'agree_terms' => true,
            'cart_items' => [['id' => $cart->id, 'quantity' => 1]],
        ])->assertStatus(201);

        $line = TransactionSellLine::query()->sole();
        $this->assertEquals(12.5, (float) $line->unit_price);
        $this->assertEquals(12.5, (float) $line->subtotal);

        $transaction = Transaction::query()->sole();
        $this->assertEquals(13.25, (float) $transaction->subtotal);
        $this->assertEquals(28.25, (float) $transaction->total);
    }
}
