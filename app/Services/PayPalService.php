<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use RuntimeException;

class PayPalService
{
    public function __construct(private HttpFactory $http) {}

    public function createOrder(Transaction $transaction, TransactionPayment $payment): array
    {
        $response = $this->withAccessToken()->post(
            $this->apiUrl('/v2/checkout/orders'),
            [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'transaction-'.$transaction->id,
                        'invoice_id' => (string) ($transaction->invoice_no ?? $transaction->id),
                        'custom_id' => (string) $transaction->id,
                        'amount' => [
                            'currency_code' => strtoupper((string) $payment->currency),
                            'value' => number_format((float) $payment->amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'brand_name' => (string) config('app.name'),
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING',
                ],
            ]
        );

        return $this->decodeResponse($response);
    }

    public function captureOrder(string $orderId): array
    {
        $orderId = trim($orderId);

        if ($orderId === '') {
            throw new RuntimeException('PayPal order ID is missing.');
        }

        $response = $this->withAccessToken()
            ->withHeaders([
                'Prefer' => 'return=representation',
            ])
            ->withBody('{}', 'application/json')
            ->post($this->apiUrl("/v2/checkout/orders/{$orderId}/capture"));

        return $this->decodeResponse($response);
    }

    public function getOrder(string $orderId): array
    {
        $response = $this->withAccessToken()->get(
            $this->apiUrl("/v2/checkout/orders/{$orderId}")
        );

        return $this->decodeResponse($response);
    }

    private function withAccessToken()
    {
        return $this->http
            ->acceptJson()
            ->withToken($this->getAccessToken());
    }

    private function getAccessToken(): string
    {
        $clientId = (string) config('services.paypal.client_id');
        $clientSecret = (string) config('services.paypal.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('PayPal credentials are not configured.');
        }

        $response = $this->http
            ->asForm()
            ->acceptJson()
            ->withBasicAuth($clientId, $clientSecret)
            ->post(
                $this->apiUrl('/v1/oauth2/token'),
                ['grant_type' => 'client_credentials']
            );

        $payload = $this->decodeResponse($response);
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Failed to retrieve PayPal access token.');
        }

        return $accessToken;
    }

    private function apiUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('services.paypal.base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('PayPal base URL is not configured.');
        }

        return $baseUrl.$path;
    }

    private function decodeResponse(Response $response): array
    {
        $response->throw();

        return $response->json();
    }
}
