<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class ShippingQuoteService
{
    public function __construct(protected PostcodeDistanceService $postcodeDistance) {}

    /**
     * Quote delivery for cart/product lines grouped by seller.
     *
     * @param  Collection<int, Product>|iterable<int, Product>  $products
     * @return array{
     *     currency: string,
     *     total_fee: float,
     *     seller_count: int,
     *     sellers: array<int, array<string, mixed>>
     * }
     */
    public function quoteForProducts(iterable $products, string $buyerPostcode): array
    {
        $buyerPostcode = $this->postcodeDistance->normalize($buyerPostcode);

        if ($buyerPostcode === '') {
            throw new RuntimeException('Buyer postcode is required to calculate shipping.');
        }

        /** @var Collection<int|string, Collection<int, Product>> $bySeller */
        $bySeller = collect($products)
            ->filter(fn ($product) => $product instanceof Product)
            ->groupBy(fn (Product $product) => $product->owner_id);

        if ($bySeller->isEmpty()) {
            throw new RuntimeException('No products available to quote shipping for.');
        }

        $sellers = [];
        $totalFee = 0.0;

        foreach ($bySeller as $sellerId => $sellerProducts) {
            /** @var User|null $seller */
            $seller = $sellerProducts->first()?->relationLoaded('owner')
                ? $sellerProducts->first()->owner
                : User::query()->find($sellerId);

            if (! $seller) {
                throw new RuntimeException('Seller profile not found for one or more cart items.');
            }

            $quote = $this->quoteForSeller($seller, $buyerPostcode);
            $quote['product_ids'] = $sellerProducts->pluck('id')->values()->all();
            $quote['product_count'] = $sellerProducts->count();

            $sellers[] = $quote;
            $totalFee += $quote['fee'];
        }

        return [
            'currency' => (string) config('shipping.currency', 'GBP'),
            'total_fee' => round($totalFee, 2),
            'seller_count' => count($sellers),
            'sellers' => $sellers,
        ];
    }

    /**
     * @return array{
     *     seller_id: int,
     *     seller_name: string,
     *     from_postcode: ?string,
     *     to_postcode: string,
     *     distance_miles: ?float,
     *     fee: float,
     *     band: string,
     *     service: string
     * }
     */
    public function quoteForSeller(User $seller, string $buyerPostcode): array
    {
        $buyerPostcode = $this->postcodeDistance->normalize($buyerPostcode);
        $sellerPostcode = $this->postcodeDistance->normalize((string) ($seller->postal_code ?? ''));
        $defaultPackage = config('shipping.default_package', []);
        $service = $this->parcelTypeFromPackage($defaultPackage);

        if ($sellerPostcode === '') {
            $fee = round((float) config('shipping.fallback_fee', 7.99), 2);

            return [
                'seller_id' => $seller->id,
                'seller_name' => (string) $seller->name,
                'from_postcode' => null,
                'to_postcode' => $this->postcodeDistance->format($buyerPostcode),
                'distance_miles' => null,
                'fee' => $fee,
                'band' => 'fallback',
                'service' => $service,
            ];
        }

        $distance = $this->postcodeDistance->distanceMiles($sellerPostcode, $buyerPostcode);
        [$fee, $band] = $this->feeForDistance($distance['distance_miles']);

        return [
            'seller_id' => $seller->id,
            'seller_name' => (string) $seller->name,
            'from_postcode' => $distance['from']['postcode'],
            'to_postcode' => $distance['to']['postcode'],
            'distance_miles' => $distance['distance_miles'],
            'fee' => $fee,
            'band' => $band,
            'service' => $service,
        ];
    }

    public function feeForDistance(float $distanceMiles): array
    {
        $bands = config('shipping.distance_bands', []);

        foreach ($bands as $index => $band) {
            $maxMiles = $band['max_miles'] ?? null;
            $fee = round((float) ($band['fee'] ?? 0), 2);

            if ($maxMiles === null || $distanceMiles <= (float) $maxMiles) {
                $label = $maxMiles === null
                    ? '200_plus'
                    : ($index === 0 ? "0_{$maxMiles}" : "upto_{$maxMiles}");

                return [$fee, $label];
            }
        }

        return [round((float) config('shipping.fallback_fee', 7.99), 2), 'fallback'];
    }

    /**
     * @param  array<string, mixed>  $packageDetails
     */
    public function parcelTypeFromPackage(array $packageDetails): string
    {
        $weightG = (int) ($packageDetails['weight_g'] ?? 0);
        $length = (int) ($packageDetails['length_cm'] ?? 0);
        $width = (int) ($packageDetails['width_cm'] ?? 0);
        $height = (int) ($packageDetails['height_cm'] ?? 0);

        $sorted = [$length, $width, $height];
        sort($sorted);

        if ($weightG <= 1000 && $sorted[0] <= 3 && $sorted[1] <= 23 && $sorted[2] <= 35) {
            return 'POSTABLE';
        }

        return 'STANDARD';
    }
}
