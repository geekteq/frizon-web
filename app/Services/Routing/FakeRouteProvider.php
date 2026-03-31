<?php

declare(strict_types=1);

require_once __DIR__ . '/RouteProviderInterface.php';

class FakeRouteProvider implements RouteProviderInterface
{
    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        // Haversine straight-line distance, multiplied by 1.3 for road approximation
        $earthRadius = 6371;
        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;
        $straightLine = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
        $roadDistance = round($straightLine * 1.3, 2);

        // Assume average 70 km/h for provider ETA
        $etaMinutes = (int) round($roadDistance / 70 * 60);

        return [
            'distance_km'        => $roadDistance,
            'provider_eta_minutes' => $etaMinutes,
            'geometry'           => null,
            'provider_name'      => 'fake',
        ];
    }
}
