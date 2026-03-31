<?php
/**
 * Test: JSON export structure.
 * Run: php tests/test_json_export.php
 */

require_once dirname(__DIR__) . '/app/Services/Export/JsonTripExporter.php';

$trip = ['title' => 'Normandie 2026', 'start_date' => '2026-06-15', 'end_date' => '2026-07-01'];
$stops = [
    ['place_name' => 'Hammarö Ställplats', 'lat' => 59.3299, 'lng' => 13.5227, 'stop_order' => 1, 'stop_type' => 'stellplatz', 'note' => 'Start'],
    ['place_name' => 'Café Sjökanten', 'lat' => 58.7530, 'lng' => 17.0086, 'stop_order' => 2, 'stop_type' => 'fika', 'note' => null],
];

$exporter = new JsonTripExporter();
$json = $exporter->export($trip, $stops);

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

$data = json_decode($json, true);

check('Valid JSON', $data !== null);
check('Has trip key', isset($data['trip']));
check('Has stops key', isset($data['stops']));
check('Has source key', isset($data['source']));
check('Trip title correct', ($data['trip']['title'] ?? '') === 'Normandie 2026');
check('Two stops', count($data['stops'] ?? []) === 2);
check('First stop name', ($data['stops'][0]['name'] ?? '') === 'Hammarö Ställplats');
check('First stop lat is float', is_float($data['stops'][0]['lat'] ?? null));
check('Source is Frizon.org', ($data['source'] ?? '') === 'Frizon.org');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
