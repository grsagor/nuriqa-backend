<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class PlatformFeeService
{
    public const CACHE_KEY = 'platform_settings.fee_percentage';

    public const ADMIN_FEE_CACHE_KEY = 'platform_settings.admin_fee_amount';

    /**
     * Fixed Nuriqa administration fee per cart/order line (GBP).
     * Separate from item/contribution, cause allocation, delivery and processor costs.
     */
    public const DEFAULT_ADMIN_FEE_AMOUNT = 0.75;

    /** @deprecated Alias for DEFAULT_ADMIN_FEE_AMOUNT */
    public const MIN_LINE_BUYER_PROTECTION_AMOUNT = self::DEFAULT_ADMIN_FEE_AMOUNT;

    public static function feePercentage(): float
    {
        return (float) Cache::remember(self::CACHE_KEY, 300, function () {
            $row = PlatformSetting::query()->first();

            return $row ? (float) $row->fee_percentage : 0.0;
        });
    }

    public static function adminFeeAmount(): float
    {
        return (float) Cache::remember(self::ADMIN_FEE_CACHE_KEY, 300, function () {
            $row = PlatformSetting::query()->first();
            if ($row && $row->admin_fee_amount !== null) {
                return round((float) $row->admin_fee_amount, 2);
            }

            return self::DEFAULT_ADMIN_FEE_AMOUNT;
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ADMIN_FEE_CACHE_KEY);
    }

    public static function isPaidProductPrice(Product $product, ?float $unitPrice = null): bool
    {
        $u = $unitPrice ?? (float) ($product->price ?? 0);
        if ($product->is_free || self::isFlexibleContribution($product)) {
            return false;
        }

        return $u > 0;
    }

    public static function isFlexibleContribution(Product $product): bool
    {
        return ($product->contribution_mode ?? 'fixed') === 'flexible'
            || $product->is_free === true
            || (float) ($product->price ?? 0) <= 0;
    }

    public static function platformFeeAmountForUnitPrice(float $unitPrice, Product $product): float
    {
        return self::adminFeeAmount();
    }

    public static function unitPriceIncludingPlatformFee(float $unitPrice, Product $product): float
    {
        return round((float) $unitPrice + self::adminFeeAmount(), 2);
    }

    public static function platformFeeAmountForSellerSubtotal(float $sellerSubtotal, Product $product): float
    {
        return self::adminFeeAmount();
    }

    public static function donationAmountForLine(float $sellerSubtotal, Product $product): float
    {
        $type = $product->cause_allocation_type ?: ($product->platform_donation ? 'percentage' : 'none');

        return match ($type) {
            'fixed' => round(max(0, (float) ($product->cause_allocation_value ?? 0)), 2),
            'percentage', 'origin_profit' => round(
                $sellerSubtotal * (max(0, (float) ($product->cause_allocation_value ?? $product->donation_percentage ?? 0)) / 100),
                2
            ),
            default => self::legacyDonationAmount($sellerSubtotal, $product),
        };
    }

    private static function legacyDonationAmount(float $sellerSubtotal, Product $product): float
    {
        if (! $product->platform_donation || (int) $product->donation_percentage <= 0) {
            return 0.0;
        }

        return round($sellerSubtotal * ((float) $product->donation_percentage / 100), 2);
    }
}
