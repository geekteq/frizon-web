<?php
/**
 * Test: Google Maps link export.
 * Run: php tests/test_google_maps_export.php
 */

require_once dirname(__DIR__) . '/app/Services/Export/GoogleMapsLinkExporter.php';

$trip = ['title' => 'Normandie 2026'];
$stops = [
    ['place_name' => 'Hammarö Ställplats', 'lat' => 59.3299, 'lng' => 13.5227, 'stop_order' => 1, 'stop_type' => 'stellplatz', 'note' => null],
    ['place_name' => 'Camping Le Grand Large', 'lat' => 48.8400, 'lng' => -1.5050, 'stop_order' => 2, 'stop_type' => 'camping', 'note' => null],
    ['place_name' => 'Café Sjökanten', 'lat' => 58.7530, 'lng' => 17.0086, 'stop_order' => 3, 'stop_type' => 'fika', 'note' => null],
];

$exporter = new GoogleMapsLinkExporter();
$text = $exporter->export($trip, $stops);

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

check('Contains trip title', str_contains($text, 'Normandie 2026'));
check('Contains first stop name', str_contains($text, 'Hammarö Ställplats'));
check('Contains Google Maps URL', str_contains($text, 'google.com/maps'));
check('Contains individual place link', str_contains($text, 'maps?q=59.3299'));
check('Contains route link', str_contains($text, 'maps/dir/'));
check('Route has all coordinates', str_contains($text, '48.84'));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
