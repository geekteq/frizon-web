<?php

declare(strict_types=1);

interface RouteProviderInterface
{
    /**
     * Get road route between two coordinates.
     *
     * @return array{distance_km: float, provider_eta_minutes: int, geometry: ?string, provider_name: string}
     */
    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array;
}
