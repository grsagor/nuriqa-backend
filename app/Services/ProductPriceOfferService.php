<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPriceOffer;
use App\Models\SellerNotification;
use App\Models\User;
use Carbon\Carbon;

class ProductPriceOfferService
{
    public static function activeApprovedOfferFor(int $buyerId, int $productId): ?ProductPriceOffer
    {
        return ProductPriceOffer::query()
            ->where('buyer_id', $buyerId)
            ->where('product_id', $productId)
            ->where('status', ProductPriceOffer::STATUS_APPROVED)
            ->whereNull('consumed_at')
            ->where(function ($q): void {
                $q->whereNull('approved_until')
                    ->orWhere('approved_until', '>', Carbon::now());
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Seller list price unit (what the listing shows as product price).
     */
    public static function listSellerUnitPrice(Product $product): float
    {
        return round((float) ($product->price ?? 0), 2);
    }

    /**
     * Unit price used for seller subtotal at checkout: approved offer or list price.
     */
    public static function resolvedSellerUnitPrice(Product $product, ?User $buyer): float
    {
        $list = self::listSellerUnitPrice($product);
        if (! $buyer || $list <= 0 || $product->is_free) {
            return $list;
        }

        $offer = self::activeApprovedOfferFor((int) $buyer->id, (int) $product->id);

        return $offer ? round((float) $offer->offered_unit_price, 2) : $list;
    }

    public static function markPendingExpired(ProductPriceOffer $offer): void
    {
        if ($offer->status !== ProductPriceOffer::STATUS_PENDING) {
            return;
        }
        $offer->forceFill(['status' => ProductPriceOffer::STATUS_EXPIRED])->save();
    }

    public static function markApprovedExpired(ProductPriceOffer $offer): void
    {
        if ($offer->status !== ProductPriceOffer::STATUS_APPROVED) {
            return;
        }
        if ($offer->consumed_at !== null) {
            return;
        }
        if ($offer->approved_until === null || Carbon::now()->lt(Carbon::parse($offer->approved_until))) {
            return;
        }
        $offer->forceFill(['status' => ProductPriceOffer::STATUS_EXPIRED])->save();
    }

    /**
     * Decline other pending offers from the same buyer for this product.
     */
    public static function declineOtherPendingForBuyerProduct(int $productId, int $buyerId, int $exceptOfferId): void
    {
        ProductPriceOffer::query()
            ->where('product_id', $productId)
            ->where('buyer_id', $buyerId)
            ->where('status', ProductPriceOffer::STATUS_PENDING)
            ->where('id', '!=', $exceptOfferId)
            ->update(['status' => ProductPriceOffer::STATUS_DECLINED]);
    }

    public static function notifySellerNewOffer(ProductPriceOffer $offer): void
    {
        $offer->loadMissing(['product', 'buyer']);
        $product = $offer->product;
        if (! $product) {
            return;
        }
        $sellerId = (int) $product->owner_id;
        if ($sellerId < 1) {
            return;
        }

        $buyerName = $offer->buyer?->name ?? 'A customer';
        $title = 'New price offer';
        $description = sprintf(
            '%s offered £%s on "%s".',
            $buyerName,
            number_format((float) $offer->offered_unit_price, 2),
            $product->title
        );

        SellerNotification::firstOrCreate(
            [
                'user_id' => $sellerId,
                'type' => 'price_offer',
                'entity_id' => $offer->id,
            ],
            [
                'entity_type' => ProductPriceOffer::class,
                'title' => $title,
                'description' => $description,
                'read' => 0,
            ]
        );
    }

    public static function notifyBuyerOfferApproved(ProductPriceOffer $offer): void
    {
        $offer->loadMissing('product');
        $product = $offer->product;
        if (! $product || (int) $offer->buyer_id < 1) {
            return;
        }

        $until = $offer->approved_until
            ? Carbon::parse($offer->approved_until)->format('M j, Y')
            : null;

        $description = sprintf(
            'Your offer of £%s on "%s" has been approved.%s',
            number_format((float) $offer->offered_unit_price, 2),
            $product->title,
            $until ? " Complete checkout before {$until}." : ''
        );

        SellerNotification::updateOrCreate(
            [
                'user_id' => (int) $offer->buyer_id,
                'type' => 'price_offer_response',
                'entity_id' => $offer->id,
            ],
            [
                'entity_type' => ProductPriceOffer::class,
                'title' => 'Offer approved',
                'description' => $description,
                'read' => 0,
            ]
        );
    }

    public static function notifyBuyerOfferDeclined(ProductPriceOffer $offer): void
    {
        $offer->loadMissing('product');
        $product = $offer->product;
        if (! $product || (int) $offer->buyer_id < 1) {
            return;
        }

        $description = sprintf(
            'Your offer of £%s on "%s" was declined by the seller.',
            number_format((float) $offer->offered_unit_price, 2),
            $product->title
        );

        SellerNotification::updateOrCreate(
            [
                'user_id' => (int) $offer->buyer_id,
                'type' => 'price_offer_response',
                'entity_id' => $offer->id,
            ],
            [
                'entity_type' => ProductPriceOffer::class,
                'title' => 'Offer declined',
                'description' => $description,
                'read' => 0,
            ]
        );
    }
}
