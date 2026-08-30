<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SponsorRequest;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionSellLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SellerDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_seller_dashboard_stats(): void
    {
        $this->getJson('/api/v1/seller/dashboard/stats')
            ->assertStatus(401);
    }

    public function test_authenticated_seller_receives_dashboard_stats(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $token = JWTAuth::fromUser($seller);

        Product::create([
            'owner_id' => $seller->id,
            'title' => 'Free listing',
            'type' => 'seller',
            'condition' => 'used',
            'is_free' => true,
            'price' => 0,
            'stock' => 5,
            'active_listing' => true,
        ]);

        $sellProduct = Product::create([
            'owner_id' => $seller->id,
            'title' => 'New item',
            'type' => 'seller',
            'condition' => 'new',
            'is_free' => false,
            'price' => 100,
            'stock' => 5,
            'active_listing' => true,
            'platform_donation' => true,
            'donation_percentage' => 10,
        ]);

        $resellProduct = Product::create([
            'owner_id' => $seller->id,
            'title' => 'Used item',
            'type' => 'seller',
            'condition' => 'used',
            'is_free' => false,
            'price' => 50,
            'stock' => 5,
            'active_listing' => true,
        ]);

        Product::create([
            'owner_id' => $seller->id,
            'title' => 'Inactive used item',
            'type' => 'seller',
            'condition' => 'used',
            'is_free' => false,
            'price' => 40,
            'stock' => 5,
            'active_listing' => false,
        ]);

        SponsorRequest::create([
            'user_id' => $buyer->id,
            'product_id' => $sellProduct->id,
            'request_reason' => 'Need help',
            'first_name' => 'John',
            'last_name' => 'Buyer',
            'email' => 'buyer@example.com',
            'phone' => '1234567890',
            'address' => '1 Main St',
            'city' => 'London',
            'postal_code' => 'E1 1AA',
            'status' => 'pending',
        ]);

        $pendingOrder = Transaction::create([
            'user_id' => $buyer->id,
            'invoice_no' => 'INV-SELL-PEND',
            'status' => 'pending',
            'subtotal' => 100,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 100,
            'billing_first_name' => 'John',
            'billing_last_name' => 'Buyer',
            'billing_email' => 'buyer@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $pendingOrder->id,
            'product_id' => $sellProduct->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'platform_fee_amount' => 0,
            'donation_amount' => 0,
        ]);

        $completedOrder = Transaction::create([
            'user_id' => $buyer->id,
            'invoice_no' => 'INV-SELL-COMP',
            'status' => 'completed',
            'subtotal' => 100,
            'platform_fee_total' => 0,
            'donation_total' => 10,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 100,
            'billing_first_name' => 'John',
            'billing_last_name' => 'Buyer',
            'billing_email' => 'buyer@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        TransactionPayment::create([
            'transaction_id' => $completedOrder->id,
            'payment_method' => 'stripe',
            'amount' => 100,
            'currency' => 'GBP',
            'status' => 'succeeded',
        ]);

        TransactionSellLine::create([
            'transaction_id' => $completedOrder->id,
            'product_id' => $sellProduct->id,
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'platform_fee_amount' => 0,
            'donation_amount' => 10,
        ]);

        $sponsoredOrder = Transaction::create([
            'user_id' => $buyer->id,
            'invoice_no' => 'INV-SELL-SPON',
            'status' => 'completed',
            'subtotal' => 0,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 0,
            'billing_first_name' => 'John',
            'billing_last_name' => 'Buyer',
            'billing_email' => 'buyer@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $sponsoredOrder->id,
            'product_id' => $resellProduct->id,
            'sponsor_request_id' => 1,
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
            'platform_fee_amount' => 0,
            'donation_amount' => 0,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/seller/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_sales', 100)
            ->assertJsonPath('data.platform_donations_made', 10)
            ->assertJsonPath('data.items_sponsored', 1)
            ->assertJsonPath('data.pending_requests', 1)
            ->assertJsonPath('data.items_listed_for_donation', 1)
            ->assertJsonPath('data.active_sell_items', 1)
            ->assertJsonPath('data.active_resell_items', 1)
            ->assertJsonPath('data.orders_pending', 1);
    }
}
