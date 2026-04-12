<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicSellerController extends Controller
{
    /**
     * Public seller storefront summary (no private fields).
     */
    public function show(Request $request, string $id)
    {
        $user = User::query()->find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found',
            ], 404);
        }

        $donatedCount = Product::query()
            ->where('owner_id', $user->id)
            ->where(function ($q) {
                $q->where('is_free', 1)
                    ->orWhere('platform_donation', 1);
            })
            ->count();

        $soldQuantity = (int) DB::table('transaction_sell_lines')
            ->join('transactions', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_sell_lines.product_id')
            ->where('products.owner_id', $user->id)
            ->where('transactions.status', 'completed')
            ->sum('transaction_sell_lines.quantity');

        $memberSince = '';
        if ($user->signup_date) {
            try {
                $memberSince = Carbon::parse($user->signup_date)->format('d-m-Y');
            } catch (\Throwable $e) {
                $memberSince = (string) $user->signup_date;
            }
        } elseif ($user->created_at) {
            $memberSince = $user->created_at->format('d-m-Y');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'image_url' => $user->image_url,
                'rating' => $user->rating,
                'reviews_count' => $user->reviews,
                'member_since' => $memberSince,
                'donated_items_count' => $donatedCount,
                'sold_items_count' => $soldQuantity,
            ],
        ]);
    }
}
