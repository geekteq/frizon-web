<?php

declare(strict_types=1);

require_once __DIR__ . '/RouteProviderInterface.php';

class OpenRouteServiceProvider implements RouteProviderInterface
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $url = 'https://api.openrouteservice.org/v2/directions/driving-car';

        $body = json_encode([
            'coordinates' => [
                [$fromLng, $fromLat],  // ORS uses [lng, lat] order
                [$toLng, $toLat],
            ],
            'options' => [
                'maximum_speed' => 95,  // Frizze's max cruising speed
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json, application/geo+json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            // Fallback to fake provider on error
            require_once __DIR__ . '/FakeRouteProvider.php';
            $fake = new FakeRouteProvider();
            return $fake->getRoute($fromLat, $fromLng, $toLat, $toLng);
        }

        $data = json_decode($response, true);

        if (empty($data['routes'][0])) {
            require_once __DIR__ . '/FakeRouteProvider.php';
            $fake = new FakeRouteProvider();
            return $fake->getRoute($fromLat, $fromLng, $toLat, $toLng);
        }

        $route = $data['routes'][0];
        $summary = $route['summary'];

        return [
            'distance_km'         => round($summary['distance'] / 1000, 2),
            'provider_eta_minutes' => (int) round($summary['duration'] / 60),
            'geometry'            => $route['geometry'] ?? null,
            'provider_name'       => 'openrouteservice',
        ];
    }
}
