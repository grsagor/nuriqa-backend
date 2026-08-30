<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SponsorRequest;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use Illuminate\Database\Eloquent\Builder;

class SellerDashboardStatsService
{
    /**
     * @return array{
     *     total_sales: float,
     *     platform_donations_made: float,
     *     items_sponsored: int,
     *     pending_requests: int,
     *     items_listed_for_donation: int,
     *     active_sell_items: int,
     *     active_resell_items: int,
     *     orders_pending: int
     * }
     */
    public function summary(int $sellerId): array
    {
        $completedPaidSellLineQuery = fn () => TransactionSellLine::query()
            ->whereHas('product', fn (Builder $q) => $q->where('owner_id', $sellerId))
            ->whereNull('sponsor_request_id')
            ->whereHas('transaction', function (Builder $q) {
                $q->where('status', 'completed')
                    ->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('status', 'succeeded'));
            });

        $totalSales = (float) $completedPaidSellLineQuery()->sum('subtotal');

        $platformDonations = (float) (TransactionSellLine::query()
            ->join('products', 'products.id', '=', 'transaction_sell_lines.product_id')
            ->where('products.owner_id', $sellerId)
            ->whereNull('transaction_sell_lines.sponsor_request_id')
            ->where('products.platform_donation', true)
            ->whereHas('transaction', function (Builder $q) {
                $q->where('status', 'completed')
                    ->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('status', 'succeeded'));
            })
            ->selectRaw(
                'COALESCE(SUM(COALESCE(transaction_sell_lines.donation_amount, transaction_sell_lines.subtotal * products.donation_percentage / 100)), 0) as total'
            )
            ->value('total') ?? 0);

        return [
            'total_sales' => round($totalSales, 2),
            'platform_donations_made' => round($platformDonations, 2),
            'items_sponsored' => (int) TransactionSellLine::query()
                ->whereHas('product', fn (Builder $q) => $q->where('owner_id', $sellerId))
                ->whereNotNull('sponsor_request_id')
                ->count(),
            'pending_requests' => (int) SponsorRequest::query()
                ->whereHas('product', fn (Builder $q) => $q->where('owner_id', $sellerId))
                ->where('status', 'pending')
                ->count(),
            'items_listed_for_donation' => (int) Product::query()
                ->where('owner_id', $sellerId)
                ->where(function (Builder $q) {
                    $q->where('is_free', true)
                        ->orWhere('price', 0);
                })
                ->count(),
            'active_sell_items' => (int) Product::query()
                ->where('owner_id', $sellerId)
                ->where('active_listing', true)
                ->where('is_free', false)
                ->where('condition', 'new')
                ->count(),
            'active_resell_items' => (int) Product::query()
                ->where('owner_id', $sellerId)
                ->where('active_listing', true)
                ->where('is_free', false)
                ->where('condition', 'used')
                ->count(),
            'orders_pending' => (int) Transaction::query()
                ->where('status', 'pending')
                ->whereHas('sellLines.product', fn (Builder $q) => $q->where('owner_id', $sellerId))
                ->count(),
        ];
    }
}
