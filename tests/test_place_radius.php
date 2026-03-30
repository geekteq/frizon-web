<?php
/**
 * Test: Place nearby detection using Haversine formula.
 * Run: php tests/test_place_radius.php
 */

function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

$tests = [
    ['Same point', 59.3293, 18.0686, 59.3293, 18.0686, true],
    ['50m apart (approx)', 59.3293, 18.0686, 59.3297, 18.0686, true],
    ['500m apart', 59.3293, 18.0686, 59.3338, 18.0686, false],
    ['10km apart', 59.3293, 18.0686, 59.4200, 18.0686, false],
    ['Hammarö to Karlstad (~12km)', 59.3299, 13.5227, 59.3793, 13.5036, false],
];

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $lat1, $lng1, $lat2, $lng2, $expectedWithin]) {
    $dist = haversineDistance($lat1, $lng1, $lat2, $lng2);
    $isWithin = $dist <= 100;
    $ok = $isWithin === $expectedWithin;

    if ($ok) {
        printf("PASS: %s — %.1fm (within 100m: %s)\n", $name, $dist, $isWithin ? 'yes' : 'no');
        $passed++;
    } else {
        printf("FAIL: %s — %.1fm (expected within=%s, got=%s)\n", $name, $dist, $expectedWithin ? 'yes' : 'no', $isWithin ? 'yes' : 'no');
        $failed++;
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
