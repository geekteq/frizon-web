<?php
/**
 * Test: ETA 95 km/h calculation.
 * Formula: round(distance_km / 95 * 60)
 * Run: php tests/test_eta95.php
 */

$tests = [
    ['0 km',     0,       0],
    ['95 km',    95,      60],
    ['190 km',   190,     120],
    ['47.5 km',  47.5,    30],
    ['100 km',   100,     63],
    ['500 km',   500,     316],
    ['1.5 km',   1.5,     1],
];

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $distKm, $expectedMin]) {
    $result = (int) round($distKm / 95 * 60);
    $ok = $result === $expectedMin;

    if ($ok) {
        printf("PASS: %s → %d min\n", $name, $result);
        $passed++;
    } else {
        printf("FAIL: %s → got %d min, expected %d min\n", $name, $result, $expectedMin);
        $failed++;
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
