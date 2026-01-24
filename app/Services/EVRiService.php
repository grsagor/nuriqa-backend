<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EVRiService
{
    protected string $baseUrl;

    protected string $clientId;

    protected string $clientSecret;

    protected string $s3Bucket;

    public function __construct()
    {
        $this->baseUrl = config('services.evri.base_url', 'https://api.evri.com');
        $this->clientId = config('services.evri.client_id');
        $this->clientSecret = config('services.evri.client_secret');
        $this->s3Bucket = config('services.evri.s3_bucket', 'nuriqa-labels');
    }

    public function authenticate(): array
    {
        $response = Http::post($this->baseUrl.'/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'labels:create labels:read tracking:read',
        ]);

        if (! $response->successful()) {
            throw new \Exception('EVRi authentication failed: '.$response->body());
        }

        $data = $response->json();

        // Store token in cache for future use
        cache()->put('evri_access_token', $data['access_token'], $data['expires_in'] - 60);

        Log::info('EVRi authentication successful', [
            'expires_in' => $data['expires_in'],
        ]);

        return $data;
    }

    public function getAccessToken(): string
    {
        $token = cache()->get('evri_access_token');

        if (! $token) {
            $authData = $this->authenticate();
            $token = $authData['access_token'];
        }

        return $token;
    }

    public function createLabel(Transaction $transaction, array $addressTo, array $addressFrom, array $packageDetails): array
    {
        $token = $this->getAccessToken();

        // Load sellLines with product relationship
        $transaction->load('sellLines.product');

        // Get product title from first sell line
        $productTitle = 'Product';
        if ($transaction->sellLines->count() > 0) {
            $firstLine = $transaction->sellLines->first();
            $productTitle = $firstLine->product->title ?? 'Product';
        }

        $labelData = [
            'service_type' => 'parcel',
            'recipient' => [
                'name' => $addressTo['name'],
                'address_line_1' => $addressTo['address_line_1'],
                'address_line_2' => $addressTo['address_line_2'] ?? '',
                'city' => $addressTo['city'],
                'postcode' => $addressTo['postcode'],
                'country' => $addressTo['country'] ?? 'GB',
                'phone' => $addressTo['phone'] ?? '',
                'email' => $addressTo['email'] ?? '',
            ],
            'sender' => [
                'name' => $addressFrom['name'],
                'address_line_1' => $addressFrom['address_line_1'],
                'address_line_2' => $addressFrom['address_line_2'] ?? '',
                'city' => $addressFrom['city'],
                'postcode' => $addressFrom['postcode'],
                'country' => $addressFrom['country'] ?? 'GB',
                'phone' => $addressFrom['phone'] ?? '',
                'email' => $addressFrom['email'] ?? '',
            ],
            'package' => [
                'weight' => $packageDetails['weight_g'],
                'length' => $packageDetails['length_cm'],
                'width' => $packageDetails['width_cm'],
                'height' => $packageDetails['height_cm'],
                'value' => $transaction->total,
                'currency' => 'GBP',
            ],
            'reference' => 'NURIQA-'.$transaction->id,
            'description' => $productTitle,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/labels', $labelData);

        if (! $response->successful()) {
            Log::error('EVRi label creation failed', [
                'transaction_id' => $transaction->id,
                'response' => $response->body(),
            ]);
            throw new \Exception('EVRi label creation failed: '.$response->body());
        }

        $labelResponse = $response->json();

        // Store label in S3
        $labelUrl = $this->storeLabelInS3($labelResponse['label_url'], $transaction->id);

        // Create shipment record
        $shipment = Shipment::create([
            'transaction_id' => $transaction->id,
            'carrier' => 'evri',
            'tracking_number' => $labelResponse['tracking_number'],
            'label_url' => $labelUrl,
            'status' => 'created',
            'address_to' => $addressTo,
            'address_from' => $addressFrom,
            'weight_g' => $packageDetails['weight_g'],
            'dimensions_cm' => [
                'length' => $packageDetails['length_cm'],
                'width' => $packageDetails['width_cm'],
                'height' => $packageDetails['height_cm'],
            ],
        ]);

        Log::info('EVRi label created successfully', [
            'transaction_id' => $transaction->id,
            'tracking_number' => $labelResponse['tracking_number'],
            'shipment_id' => $shipment->id,
        ]);

        return [
            'shipment' => $shipment,
            'tracking_number' => $labelResponse['tracking_number'],
            'label_url' => $labelUrl,
            'evri_response' => $labelResponse,
        ];
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/tracking/'.$trackingNumber);

        if (! $response->successful()) {
            throw new \Exception('EVRi tracking lookup failed: '.$response->body());
        }

        return $response->json();
    }

    public function updateShipmentStatus(Shipment $shipment, array $trackingData): void
    {
        $status = $this->mapEVRiStatusToInternal($trackingData['status']);

        $shipment->update([
            'status' => $status,
        ]);

        // Update transaction status based on shipment status
        $transaction = $shipment->transaction;

        if ($status === 'in_transit' && $transaction->status === 'processing') {
            $transaction->update(['status' => 'processing']);
        } elseif ($status === 'delivered' && in_array($transaction->status, ['processing', 'completed'])) {
            $transaction->update(['status' => 'completed']);
        }

        Log::info('Shipment status updated', [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => $status,
            'transaction_status' => $transaction->fresh()->status,
        ]);
    }

    public function cancelLabel(Shipment $shipment): bool
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->delete($this->baseUrl.'/labels/'.$shipment->tracking_number);

        if ($response->successful()) {
            $shipment->update(['status' => 'cancelled']);

            return true;
        }

        return false;
    }

    private function storeLabelInS3(string $labelUrl, int $transactionId): string
    {
        // Download label from EVRi
        $labelContent = Http::get($labelUrl)->body();

        // Generate S3 key
        $s3Key = "labels/transaction-{$transactionId}/".basename($labelUrl);

        // Store in S3
        Storage::disk('s3')->put($s3Key, $labelContent);

        // Return public URL
        return Storage::disk('s3')->url($s3Key);
    }

    private function mapEVRiStatusToInternal(string $evriStatus): string
    {
        $statusMap = [
            'created' => 'created',
            'collected' => 'in_transit',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'in_transit',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$evriStatus] ?? 'created';
    }

    public function validateAddress(array $address): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/addresses/validate', $address);

        if (! $response->successful()) {
            throw new \Exception('EVRi address validation failed: '.$response->body());
        }

        return $response->json();
    }

    public function getServiceRates(array $packageDetails, string $fromPostcode, string $toPostcode): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/rates', [
            'from_postcode' => $fromPostcode,
            'to_postcode' => $toPostcode,
            'weight' => $packageDetails['weight_g'],
            'length' => $packageDetails['length_cm'],
            'width' => $packageDetails['width_cm'],
            'height' => $packageDetails['height_cm'],
        ]);

        if (! $response->successful()) {
            throw new \Exception('EVRi rates lookup failed: '.$response->body());
        }

        return $response->json();
    }
}
