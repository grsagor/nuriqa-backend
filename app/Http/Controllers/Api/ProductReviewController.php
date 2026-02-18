<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductReviewController extends Controller
{
    /**
     * List reviews for a product.
     */
    public function index(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $query = ProductReview::where('product_id', $productId)
            ->with('user:id,name,image')
            ->orderBy('created_at', 'desc');

        if ($request->filled('rating') && $request->rating !== 'all') {
            $rating = (int) $request->rating;
            if ($rating >= 1 && $rating <= 5) {
                $query->where('rating', $rating);
            }
        }

        $sort = $request->get('sort', 'default');
        if ($sort === 'highest') {
            $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc');
        } elseif ($sort === 'lowest') {
            $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc');
        }

        $reviews = $query->paginate($request->get('per_page', 10));

        $aggregate = ProductReview::where('product_id', $productId)
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_count')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'summary' => [
                'average_rating' => $aggregate ? round((float) $aggregate->average_rating, 1) : 0,
                'total_count' => $aggregate ? (int) $aggregate->total_count : 0,
            ],
        ]);
    }

    /**
     * Store a review for a product (authenticated user).
     */
    public function store(Request $request, string $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();
        $product = Product::find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $existing = ProductReview::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        $verifiedPurchase = $this->hasUserPurchasedProduct($user->id, $productId);

        $review = ProductReview::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'verified_purchase' => $verifiedPurchase,
        ]);

        $this->updateSellerRating($product->owner_id);

        $review->load('user:id,name,image');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'verified_purchase' => $review->verified_purchase,
                'created_at' => $review->created_at,
                'user' => [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                    'image_url' => $review->user->image_url,
                ],
            ],
        ], 201);
    }

    private function hasUserPurchasedProduct(int $userId, int $productId): bool
    {
        return Transaction::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereHas('sellLines', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();
    }

    /**
     * Recalculate and update a seller's (product owner) rating from all reviews on their products.
     */
    private function updateSellerRating(int $ownerId): void
    {
        $stats = ProductReview::query()
            ->join('products', 'products.id', '=', 'product_reviews.product_id')
            ->where('products.owner_id', $ownerId)
            ->selectRaw('AVG(product_reviews.rating) as avg_rating, COUNT(*) as total')
            ->first();

        $avg = $stats ? round((float) $stats->avg_rating, 2) : null;
        $total = $stats ? (int) $stats->total : 0;

        DB::table('users')->where('id', $ownerId)->update([
            'rating' => $avg,
            'reviews' => $total,
        ]);
    }
}
