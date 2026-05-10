<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductPriceOffer;
use App\Models\User;
use App\Services\PlatformFeeService;
use App\Services\ProductPriceOfferService;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    private function attachCheckoutPricing(Cart $cart, User $user): void
    {
        $cart->loadMissing(['product.size', 'product.category', 'product.images', 'product.owner']);
        $p = $cart->product;
        if (! $p) {
            return;
        }

        $sellerUnit = ProductPriceOfferService::resolvedSellerUnitPrice($p, $user);
        $quantity = max(1, (int) $cart->quantity);
        $sellerLineSubtotal = round($sellerUnit * $quantity, 2);
        $linePlatformFee = PlatformFeeService::platformFeeAmountForSellerSubtotal($sellerLineSubtotal, $p);
        $platformFeePerUnit = round($linePlatformFee / $quantity, 2);
        $p->setAttribute('checkout_seller_unit_price', $sellerUnit);
        $p->setAttribute('checkout_platform_fee_per_unit', $platformFeePerUnit);
        $p->setAttribute('checkout_buyer_unit_price', round($sellerUnit + $platformFeePerUnit, 2));

        $approved = ProductPriceOfferService::activeApprovedOfferFor((int) $user->id, (int) $p->id);
        $pending = ProductPriceOffer::query()
            ->where('buyer_id', $user->id)
            ->where('product_id', $p->id)
            ->where('status', ProductPriceOffer::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();

        $p->setAttribute('my_price_offer', [
            'pending' => $pending ? [
                'id' => $pending->id,
                'offered_unit_price' => (float) $pending->offered_unit_price,
                'status' => $pending->status,
            ] : null,
            'approved' => $approved ? [
                'id' => $approved->id,
                'offered_unit_price' => (float) $approved->offered_unit_price,
                'approved_until' => $approved->approved_until?->toIso8601String(),
                'status' => $approved->status,
            ] : null,
        ]);
    }

    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $carts = Cart::where('user_id', $user->id)
            ->with(['product.size', 'product.category', 'product.images', 'product.owner'])
            ->latest()
            ->get();

        foreach ($carts as $cart) {
            $this->attachCheckoutPricing($cart, $user);
        }

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
            $this->attachCheckoutPricing($existingCart, $user);

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
        $this->attachCheckoutPricing($cart, $user);

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
        $this->attachCheckoutPricing($cart, $user);

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
