<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Models\User;
use App\Services\AdminDashboardStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardOrderTypeDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_type_distribution_matches_sell_line_quantities_by_product_kind(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $pFree = Product::create([
            'owner_id' => $seller->id,
            'title' => 'Free seller item',
            'type' => 'seller',
            'condition' => 'used',
            'is_free' => true,
            'price' => 0,
            'stock' => 10,
            'active_listing' => true,
        ]);
        $pUsed = Product::create([
            'owner_id' => $seller->id,
            'title' => 'Used seller item',
            'type' => 'seller',
            'condition' => 'used',
            'is_free' => false,
            'price' => 12,
            'stock' => 10,
            'active_listing' => true,
        ]);
        $pNew = Product::create([
            'owner_id' => $seller->id,
            'title' => 'New seller item',
            'type' => 'seller',
            'condition' => 'new',
            'is_free' => false,
            'price' => 20,
            'stock' => 10,
            'active_listing' => true,
        ]);
        $pMerch = Product::create([
            'owner_id' => $seller->id,
            'title' => 'Merch',
            'type' => 'merchandise',
            'condition' => 'new',
            'is_free' => false,
            'price' => 15,
            'stock' => 10,
            'active_listing' => true,
        ]);
        $pHajra = Product::create([
            'owner_id' => $seller->id,
            'title' => 'Hajra',
            'type' => 'hajra',
            'condition' => 'new',
            'is_free' => true,
            'price' => 0,
            'stock' => 10,
            'active_listing' => true,
        ]);

        $tx = Transaction::create([
            'user_id' => $buyer->id,
            'invoice_no' => 'INV-OTD-1',
            'status' => 'completed',
            'subtotal' => 100,
            'platform_fee_total' => 0,
            'donation_total' => 0,
            'tax' => 0,
            'delivery_fee' => 0,
            'coupon_discount' => 0,
            'total' => 100,
            'billing_first_name' => 'A',
            'billing_last_name' => 'B',
            'billing_email' => 'buyer@example.com',
            'billing_phone' => '1',
            'donate_anonymous' => false,
            'payment_method' => 'card',
            'keep_updated' => false,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $tx->id,
            'product_id' => $pFree->id,
            'quantity' => 2,
            'unit_price' => 0,
            'subtotal' => 0,
            'platform_fee_amount' => 0,
            'donation_amount' => null,
        ]);
        TransactionSellLine::create([
            'transaction_id' => $tx->id,
            'product_id' => $pUsed->id,
            'quantity' => 3,
            'unit_price' => 12,
            'subtotal' => 36,
            'platform_fee_amount' => 0,
            'donation_amount' => null,
        ]);
        TransactionSellLine::create([
            'transaction_id' => $tx->id,
            'product_id' => $pNew->id,
            'quantity' => 5,
            'unit_price' => 20,
            'subtotal' => 100,
            'platform_fee_amount' => 0,
            'donation_amount' => null,
        ]);
        TransactionSellLine::create([
            'transaction_id' => $tx->id,
            'product_id' => $pMerch->id,
            'quantity' => 7,
            'unit_price' => 15,
            'subtotal' => 105,
            'platform_fee_amount' => 0,
            'donation_amount' => null,
        ]);
        TransactionSellLine::create([
            'transaction_id' => $tx->id,
            'product_id' => $pHajra->id,
            'quantity' => 11,
            'unit_price' => 0,
            'subtotal' => 0,
            'platform_fee_amount' => 0,
            'donation_amount' => null,
        ]);

        $stats = app(AdminDashboardStatsService::class)->summary(30);

        $this->assertSame(
            [2, 3, 5, 7, 11],
            $stats['order_type_values'],
            'Order: free, used, new, merchandise, hajra quantities'
        );
        $this->assertSame(28, $stats['order_type_total']);
    }

    public function test_admin_dashboard_includes_order_type_chart(): void
    {
        $admin = User::factory()->create([
            'role_id' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.index'))
            ->assertOk()
            ->assertSee('Order type mix', false)
            ->assertSee('dashboardOrderTypeChart', false);
    }
}
