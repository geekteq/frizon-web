<?php
/**
 * Test: GPX export structure.
 * Run: php tests/test_gpx_export.php
 */

require_once dirname(__DIR__) . '/app/Services/Export/GpxTripExporter.php';

$trip = ['title' => 'Normandie 2026', 'intro_text' => 'Sommarresa med Frizze'];
$stops = [
    ['place_name' => 'Hammarö Ställplats', 'lat' => 59.3299, 'lng' => 13.5227, 'note' => 'Start'],
    ['place_name' => 'Camping Le Grand Large', 'lat' => 48.8400, 'lng' => -1.5050, 'note' => null],
    ['place_name' => 'Café Sjökanten', 'lat' => 58.7530, 'lng' => 17.0086, 'note' => 'Fika'],
];

$exporter = new GpxTripExporter();
$gpx = $exporter->export($trip, $stops);

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Parse XML
$xml = simplexml_load_string($gpx);
check('Valid XML', $xml !== false);
check('GPX version 1.1', (string) $xml['version'] === '1.1');
check('Creator is Frizon', (string) $xml['creator'] === 'Frizon.org');
check('Has metadata name', (string) $xml->metadata->name === 'Normandie 2026');
check('Has metadata desc', (string) $xml->metadata->desc === 'Sommarresa med Frizze');
check('Has 3 waypoints', count($xml->wpt) === 3);
check('First waypoint name', (string) $xml->wpt[0]->name === 'Hammarö Ställplats');
check('First waypoint lat', (string) $xml->wpt[0]['lat'] === '59.3299');
check('First waypoint lon', (string) $xml->wpt[0]['lon'] === '13.5227');
check('Has route element', isset($xml->rte));
check('Route has 3 points', count($xml->rte->rtept) === 3);
check('Route name matches', (string) $xml->rte->name === 'Normandie 2026');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
