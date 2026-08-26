<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PostcodeDistanceService
{
    /**
     * @return array{distance_miles: float, from: array{postcode: string, latitude: float, longitude: float}, to: array{postcode: string, latitude: float, longitude: float}}
     */
    public function distanceMiles(string $fromPostcode, string $toPostcode): array
    {
        $from = $this->lookup($fromPostcode);
        $to = $this->lookup($toPostcode);

        $miles = $this->haversineMiles(
            $from['latitude'],
            $from['longitude'],
            $to['latitude'],
            $to['longitude'],
        );

        return [
            'distance_miles' => round($miles, 2),
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{postcode: string, latitude: float, longitude: float}
     */
    public function lookup(string $postcode): array
    {
        $normalized = $this->normalize($postcode);

        if ($normalized === '') {
            throw new RuntimeException('Postcode is required.');
        }

        return Cache::remember("postcode:{$normalized}", now()->addDays(30), function () use ($normalized, $postcode) {
            $baseUrl = rtrim((string) config('shipping.postcodes_io.base_url'), '/');
            $timeout = (int) config('shipping.postcodes_io.timeout', 10);

            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get("{$baseUrl}/postcodes/".rawurlencode($normalized));

            if ($response->status() === 404) {
                throw new RuntimeException("Unknown UK postcode: {$postcode}");
            }

            if (! $response->successful()) {
                Log::warning('Postcodes.io lookup failed', [
                    'postcode' => $normalized,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuntimeException('Unable to look up postcode right now. Please try again.');
            }

            $result = $response->json('result');

            if (! is_array($result) || ! isset($result['latitude'], $result['longitude'])) {
                throw new RuntimeException("Invalid postcode lookup response for: {$postcode}");
            }

            return [
                'postcode' => (string) ($result['postcode'] ?? $this->format($normalized)),
                'latitude' => (float) $result['latitude'],
                'longitude' => (float) $result['longitude'],
            ];
        });
    }

    public function normalize(string $postcode): string
    {
        return strtoupper(preg_replace('/\s+/', '', $postcode) ?? '');
    }

    public function format(string $compactPostcode): string
    {
        $compact = $this->normalize($compactPostcode);

        if (strlen($compact) < 5) {
            return $compact;
        }

        return substr($compact, 0, -3).' '.substr($compact, -3);
    }

    private function haversineMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMiles = 3958.7613;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadiusMiles * asin(min(1, sqrt($a)));
    }
}
