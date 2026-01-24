<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Transaction;
use App\Services\EVRiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EVRiController extends Controller
{
    protected EVRiService $evriService;

    public function __construct(EVRiService $evriService)
    {
        $this->evriService = $evriService;
    }

    public function authenticate()
    {
        try {
            $authData = $this->evriService->authenticate();

            return response()->json([
                'message' => 'EVRi authentication successful',
                'expires_in' => $authData['expires_in'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function createLabel(Request $request, Transaction $transaction)
    {
        // TODO: Add admin role check when role system is implemented
        // if (! $request->user() || ! $request->user()->hasRole('admin')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'address_to' => 'required|array',
            'address_to.name' => 'required|string|max:100',
            'address_to.address_line_1' => 'required|string|max:100',
            'address_to.city' => 'required|string|max:50',
            'address_to.postcode' => 'required|string|max:10',
            'address_to.country' => 'nullable|string|size:2',
            'address_to.phone' => 'nullable|string|max:20',
            'address_to.email' => 'nullable|email|max:100',
            'address_from' => 'required|array',
            'address_from.name' => 'required|string|max:100',
            'address_from.address_line_1' => 'required|string|max:100',
            'address_from.city' => 'required|string|max:50',
            'address_from.postcode' => 'required|string|max:10',
            'address_from.country' => 'nullable|string|size:2',
            'address_from.phone' => 'nullable|string|max:20',
            'address_from.email' => 'nullable|email|max:100',
            'package_details' => 'required|array',
            'package_details.weight_g' => 'required|integer|min:1|max:30000',
            'package_details.length_cm' => 'required|integer|min:1|max:100',
            'package_details.width_cm' => 'required|integer|min:1|max:100',
            'package_details.height_cm' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->evriService->createLabel(
                $transaction,
                $request->address_to,
                $request->address_from,
                $request->package_details
            );

            return response()->json([
                'message' => 'Label created successfully',
                'shipment' => $result['shipment'],
                'tracking_number' => $result['tracking_number'],
                'label_url' => $result['label_url'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getTrackingInfo(Shipment $shipment)
    {
        try {
            $trackingData = $this->evriService->getTrackingInfo($shipment->tracking_number);

            return response()->json([
                'shipment' => $shipment,
                'tracking_data' => $trackingData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateTracking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tracking_number' => 'required|string',
            'status' => 'required|string',
            'timestamp' => 'required|date',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment = Shipment::where('tracking_number', $request->tracking_number)->first();

        if (! $shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        try {
            $trackingData = [
                'status' => $request->status,
                'timestamp' => $request->timestamp,
                'location' => $request->location,
                'notes' => $request->notes,
            ];

            $this->evriService->updateShipmentStatus($shipment, $trackingData);

            return response()->json([
                'message' => 'Tracking updated successfully',
                'shipment' => $shipment->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function cancelLabel(Request $request, Shipment $shipment)
    {
        // TODO: Add admin role check when role system is implemented
        // if (! $request->user() || ! $request->user()->hasRole('admin')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        try {
            $success = $this->evriService->cancelLabel($shipment);

            if ($success) {
                return response()->json([
                    'message' => 'Label cancelled successfully',
                    'shipment' => $shipment->fresh(),
                ]);
            } else {
                return response()->json(['message' => 'Failed to cancel label'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function validateAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'address_line_1' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'postcode' => 'required|string|max:10',
            'country' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->evriService->validateAddress($request->all());

            return response()->json([
                'valid' => $result['valid'],
                'suggestions' => $result['suggestions'] ?? [],
                'formatted_address' => $result['formatted_address'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_postcode' => 'required|string|max:10',
            'to_postcode' => 'required|string|max:10',
            'weight_g' => 'required|integer|min:1|max:30000',
            'length_cm' => 'required|integer|min:1|max:100',
            'width_cm' => 'required|integer|min:1|max:100',
            'height_cm' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $packageDetails = $request->only(['weight_g', 'length_cm', 'width_cm', 'height_cm']);
            $rates = $this->evriService->getServiceRates(
                $packageDetails,
                $request->from_postcode,
                $request->to_postcode
            );

            return response()->json($rates);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        // Verify webhook signature (implement based on EVRi's webhook security)
        $signature = $request->header('X-EVRi-Signature');
        $payload = $request->getContent();

        // In production, verify the signature here
        // $this->verifyWebhookSignature($signature, $payload);

        $data = $request->json()->all();

        $shipment = Shipment::where('tracking_number', $data['tracking_number'])->first();

        if (! $shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        try {
            $this->evriService->updateShipmentStatus($shipment, $data);

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            Log::error('EVRi webhook processing failed', [
                'tracking_number' => $data['tracking_number'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
}
