<?php

namespace App\Services;

use App\Models\SponsorRequest;
use App\Models\Transaction;

class CustomerDashboardStatsService
{
    /**
     * @return array{
     *     requested_items_pending: int,
     *     requested_items_approved: int,
     *     sponsored_items_requested: int,
     *     sponsored_items_received: int,
     *     ordered_items_pending: int,
     *     ordered_items_received: int
     * }
     */
    public function summary(int $userId): array
    {
        return [
            'requested_items_pending' => (int) SponsorRequest::query()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),
            'requested_items_approved' => (int) SponsorRequest::query()
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->count(),
            'sponsored_items_requested' => (int) Transaction::query()
                ->whereHas('sellLines', fn ($q) => $q->where('sponsor_user_id', $userId))
                ->where('status', 'pending')
                ->count(),
            'sponsored_items_received' => (int) Transaction::query()
                ->whereHas('sellLines', fn ($q) => $q->where('sponsor_user_id', $userId))
                ->where('status', 'completed')
                ->count(),
            'ordered_items_pending' => (int) Transaction::query()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),
            'ordered_items_received' => (int) Transaction::query()
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
        ];
    }
}
