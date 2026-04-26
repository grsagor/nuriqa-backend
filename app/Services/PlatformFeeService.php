<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class PlatformFeeService
{
    public const CACHE_KEY = 'platform_settings.fee_percentage';

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
        if (! self::isPaidProductPrice($product, $unitPrice)) {
            return 0.0;
        }
        $pct = self::feePercentage();

        return round($unitPrice * $pct / 100, 2);
    }

    public static function unitPriceIncludingPlatformFee(float $unitPrice, Product $product): float
    {
        if (! self::isPaidProductPrice($product, $unitPrice)) {
            return 0.0;
        }
        $fee = self::platformFeeAmountForUnitPrice($unitPrice, $product);

        return round((float) $unitPrice + $fee, 2);
    }

    public static function platformFeeAmountForSellerSubtotal(float $sellerSubtotal, Product $product): float
    {
        if (! self::isPaidProductPrice($product, (float) $product->price)) {
            return 0.0;
        }
        if ($sellerSubtotal <= 0) {
            return 0.0;
        }
        $pct = self::feePercentage();

        return round($sellerSubtotal * $pct / 100, 2);
    }

    public static function donationAmountForLine(float $sellerSubtotal, Product $product): float
    {
        if (! $product->platform_donation || (int) $product->donation_percentage <= 0) {
            return 0.0;
        }

        return round($sellerSubtotal * ((float) $product->donation_percentage / 100), 2);
    }
}
