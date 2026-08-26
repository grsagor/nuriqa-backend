<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutShippingRatesRequest;
use App\Http\Requests\CreateEvriLabelRequest;
use App\Http\Requests\EvriRatesRequest;
use App\Http\Requests\UpdateEvriTrackingRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Requests\ValidateEvriAddressRequest;
use App\Models\Cart;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Services\CheckoutShippingService;
use App\Services\EVRiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Throwable;

class EVRiController extends Controller
{
    public function __construct(
        protected EVRiService $evriService,
        protected CheckoutShippingService $checkoutShippingService,
    ) {}

    public function authenticate(): JsonResponse
    {
        try {
            $authData = $this->evriService->authenticate();

            return response()->json([
                'message' => 'EVRi authentication successful',
                'expires_in' => $authData['expires_in'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function checkoutRates(CheckoutShippingRatesRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        try {
            $cartItems = Cart::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $request->validated('cart_item_ids'))
                ->with('product.owner')
                ->get();

            if ($cartItems->count() !== count($request->validated('cart_item_ids'))) {
                return response()->json([
                    'message' => 'Some cart items were not found or do not belong to you.',
                ], 400);
            }

            $products = $cartItems->pluck('product')->filter();

            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products found for shipping quote.',
                ], 400);
            }

            $quote = $this->checkoutShippingService->quote(
                $products,
                $request->validated('shipping_postcode')
            );

            return response()->json([
                'success' => true,
                'data' => $quote['quote'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function createLabel(CreateEvriLabelRequest $request, Transaction $transaction): JsonResponse
    {
        try {
            $result = $this->evriService->createLabel(
                $transaction,
                $request->validated('address_to'),
                $request->validated('address_from'),
                $request->validated('package_details')
            );

            return response()->json([
                'message' => 'Label created successfully',
                'shipment' => $result['shipment'],
                'tracking_number' => $result['tracking_number'],
                'label_url' => $result['label_url'],
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getTrackingInfo(Shipment $shipment): JsonResponse
    {
        try {
            $trackingData = $this->evriService->getTrackingInfo((string) $shipment->tracking_number);

            return response()->json([
                'shipment' => $shipment->load('seller'),
                'tracking_data' => $trackingData,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateShipment(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $shipment->load('transaction.sellLines.product');

        $isBuyer = (int) $shipment->transaction->user_id === (int) $user->id;
        $isSeller = $shipment->seller_id
            ? (int) $shipment->seller_id === (int) $user->id
            : $shipment->transaction->sellLines->contains(
                fn ($line) => (int) ($line->product->owner_id ?? 0) === (int) $user->id
            );

        if (! $isBuyer && ! $isSeller) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        unset($data['status']);

        if ($data !== []) {
            $shipment->fill($data);
            $shipment->save();
        }

        if ($request->filled('status')) {
            $this->evriService->updateShipmentStatus($shipment, [
                'status' => $request->validated('status'),
            ]);
        }

        return response()->json([
            'message' => 'Shipment updated successfully',
            'shipment' => $shipment->fresh(['seller', 'transaction']),
        ]);
    }

    public function updateTracking(UpdateEvriTrackingRequest $request): JsonResponse
    {
        $shipment = Shipment::query()->where('tracking_number', $request->validated('tracking_number'))->first();

        if (! $shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        try {
            $this->evriService->updateShipmentStatus($shipment, $request->validated());

            return response()->json([
                'message' => 'Tracking updated successfully',
                'shipment' => $shipment->fresh(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function cancelLabel(Shipment $shipment): JsonResponse
    {
        try {
            $success = $this->evriService->cancelLabel($shipment);

            if ($success) {
                return response()->json([
                    'message' => 'Label cancelled successfully',
                    'shipment' => $shipment->fresh(),
                ]);
            }

            return response()->json(['message' => 'Failed to cancel label'], 500);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function validateAddress(ValidateEvriAddressRequest $request): JsonResponse
    {
        try {
            $result = $this->evriService->validateAddress($request->validated());

            return response()->json([
                'valid' => $result['valid'],
                'suggestions' => $result['suggestions'] ?? [],
                'formatted_address' => $result['formatted_address'] ?? null,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getRates(EvriRatesRequest $request): JsonResponse
    {
        try {
            $packageDetails = $request->safe()->only(['weight_g', 'length_cm', 'width_cm', 'height_cm']);
            $rates = $this->evriService->getServiceRates(
                $packageDetails,
                $request->validated('from_postcode'),
                $request->validated('to_postcode')
            );

            return response()->json($rates);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $webhookSecret = (string) config('services.evri.webhook_secret');
        $signature = (string) $request->header('X-EVRi-Signature', '');
        $payload = $request->getContent();

        if ($webhookSecret !== '' && ! hash_equals(hash_hmac('sha256', $payload, $webhookSecret), $signature)) {
            return response()->json(['message' => 'Invalid webhook signature'], 401);
        }

        $data = $request->json()->all();

        $shipment = Shipment::query()->where('tracking_number', $data['tracking_number'] ?? '')->first();

        if (! $shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        try {
            $this->evriService->updateShipmentStatus($shipment, $data);

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (Throwable $e) {
            Log::error('EVRi webhook processing failed', [
                'tracking_number' => $data['tracking_number'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
}
