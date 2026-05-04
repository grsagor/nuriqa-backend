<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Models\Withdrawal;
use Carbon\Carbon;

class AdminDashboardStatsService
{
    /**
     * @param  int  $revenueDonationChartDays  Must be 7 or 30 (daily buckets on the x-axis).
     * @return array{
     *     total_orders: int,
     *     total_revenue: float,
     *     total_donation: float,
     *     active_sellers: int,
     *     active_products: int,
     *     pending_withdrawals: int,
     *     chart_labels: list<string>,
     *     chart_revenue: list<float>,
     *     chart_donations: list<float>,
     *     chart_days: int,
     *     order_type_labels: list<string>,
     *     order_type_values: list<int>,
     *     order_type_total: int
     * }
     */
    public function summary(int $revenueDonationChartDays = 30): array
    {
        if (! in_array($revenueDonationChartDays, [7, 30], true)) {
            $revenueDonationChartDays = 30;
        }

        $totalOrders = (int) Transaction::query()
            ->whereNotIn('status', ['failed', 'cancelled'])
            ->count();

        $totalRevenue = (float) Transaction::query()
            ->where('status', 'completed')
            ->sum('total');

        $totalDonation = (float) Transaction::query()
            ->where('status', 'completed')
            ->sum('donation_total');

        $activeProducts = (int) Product::query()
            ->where('active_listing', true)
            ->where('stock', '>', 0)
            ->count();

        $activeSellers = (int) (Product::query()
            ->where('active_listing', true)
            ->where('stock', '>', 0)
            ->selectRaw('count(distinct owner_id) as aggregate')
            ->value('aggregate') ?? 0);

        $pendingWithdrawals = (int) Withdrawal::query()
            ->where('status', 'pending')
            ->count();

        $chartLabels = [];
        $chartRevenue = [];
        $chartDonations = [];

        $startDay = Carbon::today()->subDays($revenueDonationChartDays - 1)->startOfDay();
        for ($i = 0; $i < $revenueDonationChartDays; $i++) {
            $day = $startDay->copy()->addDays($i);
            $chartLabels[] = $day->format('M j');
            $chartRevenue[] = (float) Transaction::query()
                ->where('status', 'completed')
                ->whereDate('created_at', $day->toDateString())
                ->sum('total');
            $chartDonations[] = (float) Transaction::query()
                ->where('status', 'completed')
                ->whereDate('created_at', $day->toDateString())
                ->sum('donation_total');
        }

        $orderTypeRow = TransactionSellLine::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_sell_lines.product_id')
            ->whereNotIn('transactions.status', ['failed', 'cancelled'])
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN products.type = ? THEN transaction_sell_lines.quantity ELSE 0 END) as merchandise_qty',
                ['merchandise']
            )
            ->selectRaw(
                'SUM(CASE WHEN products.type = ? THEN transaction_sell_lines.quantity ELSE 0 END) as hajra_qty',
                ['hajra']
            )
            ->selectRaw(
                'SUM(CASE WHEN (products.type IS NULL OR products.type = ? OR products.type = ?) AND products.is_free = 1 THEN transaction_sell_lines.quantity ELSE 0 END) as free_qty',
                ['', 'seller']
            )
            ->selectRaw(
                'SUM(CASE WHEN (products.type IS NULL OR products.type = ? OR products.type = ?) AND COALESCE(products.is_free, 0) = 0 AND LOWER(COALESCE(products.condition, ?)) = ? THEN transaction_sell_lines.quantity ELSE 0 END) as used_qty',
                ['', 'seller', '', 'used']
            )
            ->selectRaw(
                'SUM(CASE WHEN (products.type IS NULL OR products.type = ? OR products.type = ?) AND COALESCE(products.is_free, 0) = 0 AND LOWER(COALESCE(products.condition, ?)) = ? THEN transaction_sell_lines.quantity ELSE 0 END) as new_qty',
                ['', 'seller', '', 'new']
            )
            ->first();

        $merchandiseQty = (int) ($orderTypeRow->merchandise_qty ?? 0);
        $hajraQty = (int) ($orderTypeRow->hajra_qty ?? 0);
        $freeQty = (int) ($orderTypeRow->free_qty ?? 0);
        $usedQty = (int) ($orderTypeRow->used_qty ?? 0);
        $newQty = (int) ($orderTypeRow->new_qty ?? 0);

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_donation' => $totalDonation,
            'active_sellers' => $activeSellers,
            'active_products' => $activeProducts,
            'pending_withdrawals' => $pendingWithdrawals,
            'chart_labels' => $chartLabels,
            'chart_revenue' => $chartRevenue,
            'chart_donations' => $chartDonations,
            'chart_days' => $revenueDonationChartDays,
            'order_type_labels' => [
                'Free products',
                'Used products',
                'New products',
                'Merchandise products',
                'Hajra products',
            ],
            'order_type_values' => [
                $freeQty,
                $usedQty,
                $newQty,
                $merchandiseQty,
                $hajraQty,
            ],
            'order_type_total' => $freeQty + $usedQty + $newQty + $merchandiseQty + $hajraQty,
        ];
    }
}
