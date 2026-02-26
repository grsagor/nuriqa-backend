<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SellerNotificationController extends Controller
{
    /**
     * Get unread notifications count for the authenticated seller.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $seller = JWTAuth::parseToken()->authenticate();

        $unreadCount = SellerNotification::query()
            ->where('user_id', $seller->id)
            ->where('read', 0)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * List seller notifications from the notifications table.
     */
    public function index(Request $request): JsonResponse
    {
        $seller = JWTAuth::parseToken()->authenticate();

        $notifications = SellerNotification::query()
            ->where('user_id', $seller->id)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (SellerNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'description' => $n->description,
                'created_at' => $n->created_at?->toIso8601String(),
                'time_ago' => $this->humanTimeAgo($n->created_at),
                'is_read' => (int) $n->read === 1,
            ])
            ->values()
            ->all();

        $unreadCount = count(array_filter($notifications, fn ($n) => ! $n['is_read']));

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark a notification as read (set read = 1).
     */
    public function markRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seller = JWTAuth::parseToken()->authenticate();
        $id = (int) $request->input('notification_id');

        $updated = SellerNotification::query()
            ->where('id', $id)
            ->where('user_id', $seller->id)
            ->update(['read' => 1]);

        if ($updated === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already read',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    private function humanTimeAgo(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        $diff = max(0, now()->getTimestamp() - $date->getTimestamp());
        $minutes = (int) floor($diff / 60);
        $hours = (int) floor($diff / 3600);
        $days = (int) floor($diff / 86400);

        if ($minutes < 60) {
            return ($minutes ?: 1).' minute'.($minutes === 1 ? '' : 's').' ago';
        }
        if ($hours < 24) {
            return $hours.' hour'.($hours === 1 ? '' : 's').' ago';
        }
        if ($days < 7) {
            return $days.' day'.($days === 1 ? '' : 's').' ago';
        }
        $weeks = (int) floor($days / 7);

        return $weeks.' week'.($weeks === 1 ? '' : 's').' ago';
    }
}
