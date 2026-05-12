<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductPriceOfferRequest;
use App\Models\Product;
use App\Models\ProductPriceOffer;
use App\Services\ProductPriceOfferService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductPriceOfferController extends Controller
{
    public function store(StoreProductPriceOfferRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $product = Product::query()->findOrFail((int) $request->validated('product_id'));

        if ((int) $product->owner_id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot make an offer on your own listing.',
            ], 422);
        }

        if ($product->is_free || ProductPriceOfferService::listSellerUnitPrice($product) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Offers are only available on paid listings.',
            ], 422);
        }

        $list = ProductPriceOfferService::listSellerUnitPrice($product);
        $offered = round((float) $request->validated('offered_unit_price'), 2);

        if ($offered >= $list - 0.0001) {
            return response()->json([
                'success' => false,
                'message' => 'Your offer must be below the listed price.',
            ], 422);
        }

        $active = ProductPriceOfferService::activeApprovedOfferFor((int) $user->id, (int) $product->id);
        if ($active) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an approved offer for this product. Complete checkout or wait until it expires.',
            ], 422);
        }

        $pending = ProductPriceOffer::query()
            ->where('buyer_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', ProductPriceOffer::STATUS_PENDING)
            ->first();

        if ($pending) {
            $pending->forceFill([
                'offered_unit_price' => $offered,
            ])->save();
            ProductPriceOfferService::notifySellerNewOffer($pending);

            return response()->json([
                'success' => true,
                'message' => 'Offer updated.',
                'data' => $pending->fresh(['product', 'buyer']),
            ]);
        }

        $offer = ProductPriceOffer::query()->create([
            'product_id' => $product->id,
            'buyer_id' => $user->id,
            'offered_unit_price' => $offered,
            'status' => ProductPriceOffer::STATUS_PENDING,
        ]);

        ProductPriceOfferService::notifySellerNewOffer($offer);

        return response()->json([
            'success' => true,
            'message' => 'Offer submitted.',
            'data' => $offer->load(['product', 'buyer']),
        ], 201);
    }

    public function myOffers(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $offers = ProductPriceOffer::query()
            ->where('buyer_id', $user->id)
            ->with(['product.images', 'product.owner'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    public function sellerIndex(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $productIds = Product::query()
            ->where('owner_id', $user->id)
            ->pluck('id');

        $query = ProductPriceOffer::query()
            ->whereIn('product_id', $productIds)
            ->with(['product.images', 'buyer']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $offers = $query->orderByDesc('id')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $offer = ProductPriceOffer::query()->with('product')->find($id);
        if (! $offer || ! $offer->product || (int) $offer->product->owner_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        if ($offer->status !== ProductPriceOffer::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending offers can be approved.',
            ], 422);
        }

        $list = ProductPriceOfferService::listSellerUnitPrice($offer->product);
        if ((float) $offer->offered_unit_price >= $list - 0.0001) {
            return response()->json([
                'success' => false,
                'message' => 'This offer is not below the current list price.',
            ], 422);
        }

        $days = max(1, (int) config('product_price_offer.approved_checkout_days', 7));
        $until = Carbon::now()->addDays($days);

        $offer->forceFill([
            'status' => ProductPriceOffer::STATUS_APPROVED,
            'approved_at' => Carbon::now(),
            'approved_until' => $until,
        ])->save();

        ProductPriceOfferService::declineOtherPendingForBuyerProduct(
            (int) $offer->product_id,
            (int) $offer->buyer_id,
            (int) $offer->id
        );

        ProductPriceOfferService::notifyBuyerOfferApproved($offer);

        return response()->json([
            'success' => true,
            'message' => 'Offer approved. The buyer can check out at this price until '.$until->toIso8601String(),
            'data' => $offer->fresh(['product', 'buyer']),
        ]);
    }

    public function decline(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $offer = ProductPriceOffer::query()->with('product')->find($id);
        if (! $offer || ! $offer->product || (int) $offer->product->owner_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        if ($offer->status !== ProductPriceOffer::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending offers can be declined.',
            ], 422);
        }

        $offer->forceFill(['status' => ProductPriceOffer::STATUS_DECLINED])->save();

        ProductPriceOfferService::notifyBuyerOfferDeclined($offer);

        return response()->json([
            'success' => true,
            'message' => 'Offer declined.',
            'data' => $offer,
        ]);
    }
}
