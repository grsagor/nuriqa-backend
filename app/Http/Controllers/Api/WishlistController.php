<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $wishlists = Wishlist::where('user_id', $user->id)
            ->with(['product.size', 'product.category', 'product.images', 'product.owner'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wishlists,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        // Check if product exists
        $product = Product::find($request->product_id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Check if already in wishlist
        $existingWishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingWishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Product already in wishlist',
            ], 400);
        }

        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
        ]);

        $wishlist->load(['product.size', 'product.category', 'product.images', 'product.owner']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist',
            'data' => $wishlist,
        ], 201);
    }

    public function destroy(string $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item not found',
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist',
        ]);
    }

    public function destroyByProduct(string $productId)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (! $wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item not found',
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist',
        ]);
    }

    public function check(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->exists();

        return response()->json([
            'success' => true,
            'in_wishlist' => $exists,
        ]);
    }
}
