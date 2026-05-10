<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class PlatformFeeService
{
    public const CACHE_KEY = 'platform_settings.fee_percentage';

    /**
     * Minimum buyer protection (platform) fee per cart line at checkout, in the storefront currency (GBP).
     */
    public const MIN_LINE_BUYER_PROTECTION_AMOUNT = 1.0;

    public static function feePercentage(): float
    {
        return (float) Cache::remember(self::CACHE_KEY, 300, function () {
            $row = PlatformSetting::query()->first();

            return $row ? (float) $row->fee_percentage : 0.0;
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function isPaidProductPrice(Product $product, ?float $unitPrice = null): bool
    {
        $u = $unitPrice ?? (float) ($product->price ?? 0);
        if ($product->is_free) {
            return false;
        }

        return $u > 0;
    }

    public static function platformFeeAmountForUnitPrice(float $unitPrice, Product $product): float
    {
        $pct = self::feePercentage();
        $feeFromPct = 0.0;
        if (self::isPaidProductPrice($product, $unitPrice) && $unitPrice > 0) {
            $feeFromPct = round($unitPrice * $pct / 100, 2);
        }

        return round(max($feeFromPct, self::MIN_LINE_BUYER_PROTECTION_AMOUNT), 2);
    }

    public static function unitPriceIncludingPlatformFee(float $unitPrice, Product $product): float
    {
        $fee = self::platformFeeAmountForUnitPrice($unitPrice, $product);

        return round((float) $unitPrice + $fee, 2);
    }

    public static function platformFeeAmountForSellerSubtotal(float $sellerSubtotal, Product $product): float
    {
        $pct = self::feePercentage();
        $feeFromPct = 0.0;
        if (self::isPaidProductPrice($product, (float) $product->price) && $sellerSubtotal > 0) {
            $feeFromPct = round($sellerSubtotal * $pct / 100, 2);
        }

        return round(max($feeFromPct, self::MIN_LINE_BUYER_PROTECTION_AMOUNT), 2);
    }

    public static function donationAmountForLine(float $sellerSubtotal, Product $product): float
    {
        if (! $product->platform_donation || (int) $product->donation_percentage <= 0) {
            return 0.0;
        }

        return round($sellerSubtotal * ((float) $product->donation_percentage / 100), 2);
    }
}
