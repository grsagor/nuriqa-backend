<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $carts = Cart::where('user_id', $user->id)
            ->with(['product.size', 'product.category', 'product.images', 'product.owner'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $carts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        // Check if product exists and has stock
        $product = Product::find($request->product_id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }
        $quantity = $request->input('quantity', 1);
        if ($product->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: '.$product->stock,
            ], 400);
        }

        // Check if already in cart
        $existingCart = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCart) {
            // Update quantity (ensure we don't exceed stock)
            $newQuantity = $existingCart->quantity + $quantity;
            if ($product->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: '.$product->stock,
                ], 400);
            }
            $existingCart->quantity = $newQuantity;
            $existingCart->save();

            $existingCart->load(['product.size', 'product.category', 'product.images', 'product.owner']);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated',
                'data' => $existingCart,
            ]);
        }

        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'quantity' => $quantity,
        ]);

        $cart->load(['product.size', 'product.category', 'product.images', 'product.owner']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'data' => $cart,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        $cart = Cart::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cart->load('product');
        if ($cart->product && $cart->product->stock < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: '.$cart->product->stock,
            ], 400);
        }

        $cart->quantity = $request->quantity;
        $cart->save();

        $cart->load(['product.size', 'product.category', 'product.images', 'product.owner']);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'data' => $cart,
        ]);
    }

    public function destroy(string $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $cart = Cart::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from cart',
        ]);
    }

    public function clear()
    {
        $user = JWTAuth::parseToken()->authenticate();

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared',
        ]);
    }
}
