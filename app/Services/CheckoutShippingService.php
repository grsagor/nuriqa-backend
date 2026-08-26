<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CheckoutShippingService
{
    public function __construct(
        protected ShippingQuoteService $shippingQuoteService,
        protected EVRiService $evriService,
    ) {}

    /**
     * Recalculate buyer delivery fee from cart products + buyer postcode.
     *
     * @param  iterable<int, \App\Models\Product>  $products
     * @return array{total_fee: float, quote: array<string, mixed>}
     */
    public function quote(iterable $products, string $buyerPostcode): array
    {
        $quote = $this->shippingQuoteService->quoteForProducts($products, $buyerPostcode);

        return [
            'total_fee' => $quote['total_fee'],
            'quote' => $quote,
        ];
    }

    /**
     * Create one EVRi shipment per seller after checkout.
     *
     * @param  array<string, mixed>  $shippingAddress
     * @return array<int, Shipment>
     */
    public function createShipmentsForTransaction(Transaction $transaction, array $shippingAddress): array
    {
        $transaction->loadMissing(['sellLines.product.owner']);

        $bySeller = $transaction->sellLines
            ->filter(fn ($line) => $line->product !== null)
            ->groupBy(fn ($line) => $line->product->owner_id);

        $package = config('shipping.default_package', [
            'weight_g' => 500,
            'length_cm' => 30,
            'width_cm' => 20,
            'height_cm' => 10,
        ]);

        $shipments = [];

        foreach ($bySeller as $sellerId => $lines) {
            /** @var User|null $seller */
            $seller = $lines->first()?->product?->owner;

            if (! $seller) {
                Log::warning('Skipping EVRi shipment: seller missing', [
                    'transaction_id' => $transaction->id,
                    'seller_id' => $sellerId,
                ]);

                continue;
            }

            $sellerQuote = $this->shippingQuoteService->quoteForSeller(
                $seller,
                (string) ($shippingAddress['postcode'] ?? ''),
            );

            $addressFrom = $this->sellerAddressFrom($seller);
            $productTitle = (string) ($lines->first()?->product?->title ?? 'Product');

            try {
                $result = $this->evriService->createLabelForSeller(
                    $transaction,
                    $seller,
                    $shippingAddress,
                    $addressFrom,
                    $package,
                    $productTitle,
                    (float) $sellerQuote['fee'],
                );

                $shipments[] = $result['shipment'];
            } catch (Throwable $e) {
                Log::error('EVRi shipment creation failed at checkout', [
                    'transaction_id' => $transaction->id,
                    'seller_id' => $seller->id,
                    'error' => $e->getMessage(),
                ]);

                $shipments[] = Shipment::query()->create([
                    'transaction_id' => $transaction->id,
                    'seller_id' => $seller->id,
                    'carrier' => 'evri',
                    'tracking_number' => null,
                    'label_url' => null,
                    'status' => 'pending',
                    'shipping_fee' => $sellerQuote['fee'],
                    'address_to' => $shippingAddress,
                    'address_from' => $addressFrom,
                    'weight_g' => $package['weight_g'],
                    'dimensions_cm' => [
                        'length' => $package['length_cm'],
                        'width' => $package['width_cm'],
                        'height' => $package['height_cm'],
                    ],
                ]);
            }
        }

        if ($shipments === []) {
            throw new RuntimeException('Unable to create shipping records for this order.');
        }

        return $shipments;
    }

    /**
     * @return array<string, mixed>
     */
    public function sellerAddressFrom(User $seller): array
    {
        return [
            'name' => (string) $seller->name,
            'address_line_1' => (string) ($seller->address ?: 'Address pending'),
            'address_line_2' => $seller->apartment,
            'city' => (string) ($seller->city ?: 'Unknown'),
            'postcode' => (string) ($seller->postal_code ?: ''),
            'country' => 'GB',
            'email' => $seller->email,
            'phone' => $seller->phone,
        ];
    }

    /**
     * @param  array<string, mixed>  $requestData
     * @return array<string, mixed>
     */
    public function shippingAddressFromCheckout(array $requestData, string $fullName): array
    {
        return [
            'name' => $fullName,
            'address_line_1' => (string) ($requestData['shipping_address_line_1'] ?? ''),
            'address_line_2' => $requestData['shipping_address_line_2'] ?? null,
            'city' => (string) ($requestData['shipping_city'] ?? ''),
            'postcode' => (string) ($requestData['shipping_postcode'] ?? ''),
            'country' => (string) ($requestData['shipping_country'] ?? 'GB'),
            'email' => (string) ($requestData['billing_email'] ?? ''),
            'phone' => (string) ($requestData['billing_phone'] ?? ''),
        ];
    }
}
