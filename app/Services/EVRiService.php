<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EVRiService
{
    private const PARCEL_SUMMARIES_ACCEPT = 'application/vnd.myhermes.parcelsummaries-v1+json';

    protected string $authTokenUrl;

    protected string $baseUrl;

    protected string $audience;

    protected string $authId;

    protected string $authSecret;

    protected string $oauthClientId;

    protected string $oauthClientSecret;

    protected string $apiKey;

    protected string $storageDisk;

    public function __construct()
    {
        $this->authTokenUrl = (string) config('services.evri.auth_token_url');
        $this->baseUrl = rtrim((string) config('services.evri.base_url'), '/');
        $this->audience = (string) config('services.evri.audience', $this->baseUrl);
        $this->authId = (string) config('services.evri.auth_id');
        $this->authSecret = (string) config('services.evri.auth_secret');
        $this->oauthClientId = (string) config('services.evri.oauth_client_id', $this->authId);
        $this->oauthClientSecret = (string) config('services.evri.oauth_client_secret', $this->authSecret);
        $this->apiKey = (string) config('services.evri.api_key');
        $this->storageDisk = (string) config('services.evri.storage_disk', 'public');
    }

    /**
     * @return array{access_token: string, expires_in: int, token_type?: string}
     */
    public function authenticate(): array
    {
        $this->assertConfigured();

        $tokenPayload = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->oauthClientId !== '' ? $this->oauthClientId : $this->authId,
            'client_secret' => $this->oauthClientSecret !== '' ? $this->oauthClientSecret : $this->authSecret,
        ];

        // oauth.prod.evricloud.co.uk (Auth0) requires audience.
        if ($this->audience !== '') {
            $tokenPayload['audience'] = $this->audience;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->withBasicAuth($this->authId, $this->authSecret)
            ->withHeaders($this->apiKeyHeaders())
            ->post($this->authTokenUrl, $tokenPayload);

        if (! $response->successful()) {
            throw new RuntimeException('EVRi authentication failed: '.$response->body());
        }

        $data = $response->json() ?? [];
        $accessToken = $data['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('EVRi authentication failed: access_token missing from response.');
        }

        $expiresIn = max(120, (int) ($data['expires_in'] ?? 3600));

        cache()->put('evri_access_token', $accessToken, $expiresIn - 60);

        $this->verifyApiAccess($accessToken);

        Log::info('EVRi authentication successful', [
            'expires_in' => $expiresIn,
            'audience' => $this->audience,
        ]);

        return [
            'access_token' => $accessToken,
            'expires_in' => $expiresIn,
            'token_type' => $data['token_type'] ?? 'Bearer',
        ];
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

    /**
     * @param  array<string, mixed>  $addressTo
     * @param  array<string, mixed>  $addressFrom
     * @param  array<string, mixed>  $packageDetails
     * @return array{shipment: Shipment, tracking_number: string, label_url: ?string, evri_response: array<string, mixed>}
     */
    public function createLabel(Transaction $transaction, array $addressTo, array $addressFrom, array $packageDetails): array
    {
        $transaction->loadMissing('sellLines.product.owner');

        $productTitle = 'Product';
        $sellerId = null;

        if ($transaction->sellLines->count() > 0) {
            $firstLine = $transaction->sellLines->first();
            $productTitle = $firstLine->product->title ?? 'Product';
            $sellerId = $firstLine->product->owner_id ?? null;
        }

        return $this->createParcelAndShipment(
            $transaction,
            $sellerId,
            $addressTo,
            $addressFrom,
            $packageDetails,
            $productTitle,
            null,
        );
    }

    /**
     * Create an EVRi parcel for one seller within a multi-seller transaction.
     *
     * @param  array<string, mixed>  $addressTo
     * @param  array<string, mixed>  $addressFrom
     * @param  array<string, mixed>  $packageDetails
     * @return array{shipment: Shipment, tracking_number: string, label_url: ?string, evri_response: array<string, mixed>}
     */
    public function createLabelForSeller(
        Transaction $transaction,
        \App\Models\User $seller,
        array $addressTo,
        array $addressFrom,
        array $packageDetails,
        string $productTitle,
        ?float $shippingFee = null,
    ): array {
        return $this->createParcelAndShipment(
            $transaction,
            $seller->id,
            $addressTo,
            $addressFrom,
            $packageDetails,
            $productTitle,
            $shippingFee,
        );
    }

    /**
     * @param  array<string, mixed>  $addressTo
     * @param  array<string, mixed>  $addressFrom
     * @param  array<string, mixed>  $packageDetails
     * @return array{shipment: Shipment, tracking_number: string, label_url: ?string, evri_response: array<string, mixed>}
     */
    private function createParcelAndShipment(
        Transaction $transaction,
        ?int $sellerId,
        array $addressTo,
        array $addressFrom,
        array $packageDetails,
        string $productTitle,
        ?float $shippingFee,
    ): array {
        $token = $this->getAccessToken();
        $clientUid = 'nuriqa-'.$transaction->id.'-'.($sellerId ?? 'x').'-'.uniqid();
        $parcelPayload = [
            'parcels' => [
                $this->buildParcelPayload($clientUid, $transaction, $addressTo, $packageDetails, $productTitle),
            ],
        ];

        $response = $this->authorizedJson($token)
            ->accept(self::PARCEL_SUMMARIES_ACCEPT)
            ->post($this->baseUrl.'/api/parcels', $parcelPayload);

        if (! $response->successful()) {
            Log::error('EVRi label creation failed', [
                'transaction_id' => $transaction->id,
                'seller_id' => $sellerId,
                'response' => $response->body(),
            ]);
            throw new RuntimeException('EVRi label creation failed: '.$response->body());
        }

        $labelResponse = $response->json() ?? [];
        $summary = $labelResponse['parcelSummaries'][0] ?? null;

        if (! is_array($summary)) {
            throw new RuntimeException('EVRi label creation failed: parcel summary missing from response.');
        }

        if (($summary['status'] ?? null) !== 'CREATED' || empty($summary['barcode'])) {
            $errors = json_encode($summary['errors'] ?? $summary);
            throw new RuntimeException('EVRi label creation failed: '.$errors);
        }

        $barcode = (string) $summary['barcode'];
        $labelUrl = $this->downloadAndStoreLabel($token, $barcode, $transaction->id);

        $shipment = Shipment::query()->create([
            'transaction_id' => $transaction->id,
            'seller_id' => $sellerId,
            'carrier' => 'evri',
            'tracking_number' => $barcode,
            'label_url' => $labelUrl,
            'status' => 'created',
            'shipping_fee' => $shippingFee,
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
            'seller_id' => $sellerId,
            'tracking_number' => $barcode,
            'shipment_id' => $shipment->id,
        ]);

        return [
            'shipment' => $shipment,
            'tracking_number' => $barcode,
            'label_url' => $labelUrl,
            'evri_response' => $labelResponse,
        ];
    }

    /**
     * EVRi Classic has no tracking endpoint; return the public tracking URL plus local status.
     *
     * @return array{tracking_number: string, status: string, tracking_url: string, carrier: string}
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'status' => Shipment::query()->where('tracking_number', $trackingNumber)->value('status') ?? 'created',
            'tracking_url' => 'https://www.evri.com/track-a-parcel/'.urlencode($trackingNumber),
            'carrier' => 'evri',
        ];
    }

    /**
     * @param  array<string, mixed>  $trackingData
     */
    public function updateShipmentStatus(Shipment $shipment, array $trackingData): void
    {
        $status = $this->mapEVRiStatusToInternal((string) $trackingData['status']);

        $shipment->update([
            'status' => $status,
        ]);

        $transaction = $shipment->transaction;

        if ($status === 'delivered' && in_array($transaction->status, ['processing', 'completed'], true)) {
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
        $shipment->update(['status' => 'cancelled']);

        return true;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{valid: bool, formatted_address: ?string, suggestions: array<int, mixed>}
     */
    public function validateAddress(array $address): array
    {
        $postcode = strtoupper(preg_replace('/\s+/', '', (string) ($address['postcode'] ?? '')) ?? '');
        $formattedPostcode = $this->formatUkPostcode($postcode);
        $valid = $this->isValidUkPostcode($postcode)
            && filled($address['address_line_1'] ?? null)
            && filled($address['city'] ?? null)
            && (($address['country'] ?? 'GB') === 'GB' || ($address['country'] ?? 'GB') === 'UK');

        $formatted = null;
        if ($valid) {
            $formatted = trim(implode(', ', array_filter([
                $address['name'] ?? null,
                $address['address_line_1'] ?? null,
                $address['address_line_2'] ?? null,
                $address['city'] ?? null,
                $formattedPostcode,
                $address['country'] ?? 'GB',
            ])));
        }

        return [
            'valid' => $valid,
            'formatted_address' => $formatted,
            'suggestions' => [],
        ];
    }

    /**
     * Buyer-facing rate for a single seller→buyer postcode pair.
     * EVRi Classic bills the Nuriqa account; distance bands set the buyer charge.
     *
     * @param  array<string, mixed>  $packageDetails
     * @return array{rates: array<int, array<string, mixed>>}
     */
    public function getServiceRates(array $packageDetails, string $fromPostcode, string $toPostcode): array
    {
        $weightG = (int) ($packageDetails['weight_g'] ?? 0);
        $service = $this->parcelTypeFromPackage($packageDetails);
        $distanceService = app(PostcodeDistanceService::class);
        $quoteService = app(ShippingQuoteService::class);

        $distance = $distanceService->distanceMiles($fromPostcode, $toPostcode);
        [$fee, $band] = $quoteService->feeForDistance($distance['distance_miles']);

        return [
            'rates' => [
                [
                    'service' => $service,
                    'name' => $service === 'POSTABLE' ? 'EVRi Postable' : 'EVRi Standard',
                    'currency' => (string) config('shipping.currency', 'GBP'),
                    'amount' => $fee,
                    'band' => $band,
                    'distance_miles' => $distance['distance_miles'],
                    'charged_to' => 'buyer',
                    'from_postcode' => $distance['from']['postcode'],
                    'to_postcode' => $distance['to']['postcode'],
                    'weight_g' => $weightG,
                ],
            ],
        ];
    }

    private function verifyApiAccess(string $accessToken): void
    {
        $response = $this->authorizedJson($accessToken)
            ->get($this->baseUrl.'/api/customers');

        // Some Hermes World environments expose parcels but not /api/customers.
        if ($response->successful()) {
            return;
        }

        if (in_array($response->status(), [404, 405], true)) {
            Log::warning('EVRi /api/customers not available; token accepted without customer probe', [
                'status' => $response->status(),
            ]);

            return;
        }

        throw new RuntimeException('EVRi API access failed: '.$response->body());
    }

    /**
     * @param  array<string, mixed>  $addressTo
     * @param  array<string, mixed>  $packageDetails
     * @return array<string, mixed>
     */
    private function buildParcelPayload(
        string $clientUid,
        Transaction $transaction,
        array $addressTo,
        array $packageDetails,
        string $productTitle,
    ): array {
        $nameParts = $this->splitName((string) ($addressTo['name'] ?? ''));

        return [
            'clientUID' => $clientUid,
            'parcelDetails' => [
                'weightKg' => round(((int) $packageDetails['weight_g']) / 1000, 3),
                'itemDescription' => mb_substr($productTitle, 0, 255),
                'deliveryReference' => mb_substr('NURIQA-'.$transaction->id, 0, 20),
                'estimatedParcelValuePounds' => round((float) $transaction->total, 2),
                'compensationRequiredPounds' => 0,
                'type' => $this->parcelTypeFromPackage($packageDetails),
            ],
            'deliveryDetails' => [
                'deliveryAddress' => $this->mapDeliveryAddress($addressTo),
                'firstName' => mb_substr($nameParts['first'], 0, 32),
                'lastName' => mb_substr($nameParts['last'], 0, 32),
                'email' => mb_substr((string) ($addressTo['email'] ?? ''), 0, 80) ?: null,
                'telephone' => mb_substr((string) ($addressTo['phone'] ?? ''), 0, 15) ?: null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{line1: string, line2: ?string, line3: ?string, line4: ?string, postcode: string}
     */
    private function mapDeliveryAddress(array $address): array
    {
        return [
            'line1' => mb_substr((string) $address['address_line_1'], 0, 32),
            'line2' => mb_substr((string) ($address['address_line_2'] ?? ''), 0, 32) ?: null,
            'line3' => mb_substr((string) ($address['city'] ?? ''), 0, 32) ?: null,
            'line4' => mb_substr((string) ($address['county'] ?? ''), 0, 32) ?: null,
            'postcode' => (string) $address['postcode'],
        ];
    }

    /**
     * @param  array<string, mixed>  $packageDetails
     */
    private function parcelTypeFromPackage(array $packageDetails): string
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

    /**
     * @return array{first: string, last: string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        $parts = preg_split('/\s+/', $name, 2) ?: [];

        $first = $parts[0] ?? 'Customer';
        $last = $parts[1] ?? $first;

        return [
            'first' => $first !== '' ? $first : 'Customer',
            'last' => $last !== '' ? $last : 'Customer',
        ];
    }

    private function downloadAndStoreLabel(string $token, string $barcode, int $transactionId): ?string
    {
        $response = $this->authorizedJson($token)
            ->accept('application/pdf')
            ->get($this->baseUrl.'/api/labels/'.$barcode, [
                'format' => 'DEFAULT',
            ]);

        if (! $response->successful()) {
            Log::error('EVRi label download failed', [
                'barcode' => $barcode,
                'response' => $response->body(),
            ]);

            return null;
        }

        $s3Key = "labels/transaction-{$transactionId}/{$barcode}.pdf";
        Storage::disk($this->storageDisk)->put($s3Key, $response->body());

        return Storage::disk($this->storageDisk)->url($s3Key);
    }

    private function mapEVRiStatusToInternal(string $evriStatus): string
    {
        $normalized = strtolower($evriStatus);

        $statusMap = [
            'pending' => 'pending',
            'created' => 'created',
            'collected' => 'in_transit',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'in_transit',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[$normalized] ?? 'pending';
    }

    private function authorizedJson(string $token): PendingRequest
    {
        return Http::timeout(30)
            ->withToken($token)
            ->withHeaders(array_merge($this->apiKeyHeaders(), [
                'Content-Type' => 'application/json',
            ]));
    }

    /**
     * @return array<string, string>
     */
    private function apiKeyHeaders(): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        return [
            'apikey' => $this->apiKey,
        ];
    }

    private function isValidUkPostcode(string $postcode): bool
    {
        $compact = strtoupper(preg_replace('/\s+/', '', $postcode) ?? '');

        return (bool) preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/', $compact);
    }

    private function formatUkPostcode(string $compactPostcode): string
    {
        $compact = strtoupper(preg_replace('/\s+/', '', $compactPostcode) ?? '');

        if (strlen($compact) < 5) {
            return $compact;
        }

        return substr($compact, 0, -3).' '.substr($compact, -3);
    }

    private function assertConfigured(): void
    {
        if ($this->authTokenUrl === '' || $this->baseUrl === '' || $this->authId === '' || $this->authSecret === '') {
            throw new RuntimeException('EVRi is not configured. Set EVRI_AUTH_TOKEN_URL, EVRI_BASE_URL, EVRI_AUTH_ID, and EVRI_AUTH_SECRET.');
        }

        if ($this->audience === '') {
            throw new RuntimeException('EVRi audience is not configured. Set EVRI_AUDIENCE (usually the same as EVRI_BASE_URL).');
        }
    }
}
