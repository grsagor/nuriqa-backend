<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SponsorRequest;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CustomerDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_dashboard_stats(): void
    {
        $this->getJson('/api/v1/customer/dashboard/stats')
            ->assertStatus(401);
    }

    public function test_authenticated_customer_receives_dashboard_stats(): void
    {
        $customer = User::factory()->create();
        $seller = User::factory()->create();
        $token = JWTAuth::fromUser($customer);

        $product = Product::create([
            'owner_id' => $seller->id,
            'title' => 'Donation item',
            'type' => 'seller',
            'condition' => 'used',
            'is_free' => true,
            'price' => 0,
            'stock' => 5,
            'active_listing' => true,
        ]);

        SponsorRequest::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'request_reason' => 'Need help',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'address' => '1 Main St',
            'city' => 'London',
            'postal_code' => 'E1 1AA',
            'status' => 'pending',
        ]);

        SponsorRequest::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'request_reason' => 'Need help again',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'address' => '1 Main St',
            'city' => 'London',
            'postal_code' => 'E1 1AA',
            'status' => 'approved',
        ]);

        $pendingOrder = Transaction::create([
            'user_id' => $customer->id,
            'invoice_no' => 'INV-CUST-1',
            'status' => 'pending',
            'subtotal' => 20,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 20,
            'billing_first_name' => 'Jane',
            'billing_last_name' => 'Doe',
            'billing_email' => 'jane@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        $completedOrder = Transaction::create([
            'user_id' => $customer->id,
            'invoice_no' => 'INV-CUST-2',
            'status' => 'completed',
            'subtotal' => 30,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 30,
            'billing_first_name' => 'Jane',
            'billing_last_name' => 'Doe',
            'billing_email' => 'jane@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $pendingOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
            'subtotal' => 20,
            'platform_fee_amount' => 0,
            'donation_amount' => 0,
        ]);

        $sponsoredPending = Transaction::create([
            'user_id' => $seller->id,
            'invoice_no' => 'INV-SPON-1',
            'status' => 'pending',
            'subtotal' => 0,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 0,
            'billing_first_name' => 'Seller',
            'billing_last_name' => 'One',
            'billing_email' => 'seller@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        $sponsoredCompleted = Transaction::create([
            'user_id' => $seller->id,
            'invoice_no' => 'INV-SPON-2',
            'status' => 'completed',
            'subtotal' => 0,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 0,
            'billing_first_name' => 'Seller',
            'billing_last_name' => 'One',
            'billing_email' => 'seller@example.com',
            'billing_phone' => '1234567890',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $sponsoredPending->id,
            'product_id' => $product->id,
            'sponsor_user_id' => $customer->id,
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
            'platform_fee_amount' => 0,
            'donation_amount' => 0,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $sponsoredCompleted->id,
            'product_id' => $product->id,
            'sponsor_user_id' => $customer->id,
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
            'platform_fee_amount' => 0,
            'donation_amount' => 0,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customer/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.requested_items_pending', 1)
            ->assertJsonPath('data.requested_items_approved', 1)
            ->assertJsonPath('data.sponsored_items_requested', 1)
            ->assertJsonPath('data.sponsored_items_received', 1)
            ->assertJsonPath('data.ordered_items_pending', 1)
            ->assertJsonPath('data.ordered_items_received', 1);
    }
}
