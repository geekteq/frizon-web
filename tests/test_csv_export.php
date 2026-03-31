<?php
/**
 * Test: CSV export structure.
 * Run: php tests/test_csv_export.php
 */

require_once dirname(__DIR__) . '/app/Services/Export/CsvTripExporter.php';

$trip = ['title' => 'Normandie 2026'];
$stops = [
    ['place_name' => 'Hammarö Ställplats', 'lat' => 59.3299, 'lng' => 13.5227, 'stop_order' => 1, 'stop_type' => 'stellplatz', 'note' => 'Start'],
    ['place_name' => 'Camping Le Grand Large', 'lat' => 48.8400, 'lng' => -1.5050, 'stop_order' => 2, 'stop_type' => 'camping', 'note' => null],
    ['place_name' => 'Café Sjökanten', 'lat' => 58.7530, 'lng' => 17.0086, 'stop_order' => 3, 'stop_type' => 'fika', 'note' => 'Bra fika'],
];

$exporter = new CsvTripExporter();
$csv = $exporter->export($trip, $stops);

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Check BOM
check('Has UTF-8 BOM', str_starts_with($csv, "\xEF\xBB\xBF"));

// Remove BOM for parsing
$csv = ltrim($csv, "\xEF\xBB\xBF");
$lines = explode("\n", trim($csv));

check('Has header + 3 data lines', count($lines) >= 4);
check('Header has semicolons', str_contains($lines[0], ';'));
check('Header starts with Nr', str_starts_with(trim($lines[0]), 'Nr'));
check('First stop is Hammarö', str_contains($lines[1], 'Hammarö'));
check('Stop type translated to Ställplats', str_contains($lines[1], 'Ställplats'));
check('Fika type translated', str_contains($lines[3], 'Fika'));
check('Contains coordinates', str_contains($lines[1], '59.3299'));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
