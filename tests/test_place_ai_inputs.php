<?php
/**
 * Verifies generatePlaceDraft uses (a) places.default_public_text from DB,
 * not user-supplied textarea content, and (b) only published visits.
 * Source-level inspection — keeps the test infrastructure-free.
 *
 * Run: php tests/test_place_ai_inputs.php
 */

$source = file_get_contents(__DIR__ . '/../app/Controllers/AiController.php');
if ($source === false) { fwrite(STDERR, "read failed\n"); exit(1); }

// Isolate the generatePlaceDraft method body.
$start = strpos($source, 'public function generatePlaceDraft');
$end   = strpos($source, 'public function generatePlaceSeo');
if ($start === false || $end === false) {
    fwrite(STDERR, "Could not locate generatePlaceDraft method\n");
    exit(1);
}
$method = substr($source, $start, $end - $start);

$passed = 0;
$failed = 0;
function check(string $name, bool $cond): void {
    global $passed, $failed;
    if ($cond) { echo "PASS: {$name}\n"; $passed++; }
    else        { echo "FAIL: {$name}\n"; $failed++; }
}

check(
    'Filters visits by ready_for_publish = 1',
    str_contains($method, 'ready_for_publish = 1')
);
check(
    'Does not read user-supplied current_text from request body',
    !str_contains($method, "current_text")
);
check(
    'Does not call file_get_contents on php://input inside this method',
    !str_contains($method, "php://input")
);
check(
    'Uses default_public_text from the loaded place row',
    str_contains($method, "\$place['default_public_text']")
);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
