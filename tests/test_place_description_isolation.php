<?php
/**
 * Regression test: approving a visit AI draft must NOT overwrite the
 * place's default_public_text. Reads AiController source to verify the
 * removed code is gone — keeps the test infrastructure-free.
 *
 * Run: php tests/test_place_description_isolation.php
 */

$source = file_get_contents(__DIR__ . '/../app/Controllers/AiController.php');
if ($source === false) {
    fwrite(STDERR, "Could not read AiController.php\n");
    exit(1);
}

$passed = 0;
$failed = 0;

function check(string $name, bool $cond): void {
    global $passed, $failed;
    if ($cond) { echo "PASS: {$name}\n"; $passed++; }
    else        { echo "FAIL: {$name}\n"; $failed++; }
}

// The string we removed from approveDraft:
$forbiddenSql = 'UPDATE places SET default_public_text';

check(
    'approveDraft does not write to places.default_public_text',
    !str_contains($source, $forbiddenSql)
);

// SEO regeneration after approval should still be present (different feature).
check(
    'SEO regeneration block still present',
    str_contains($source, 'generatePlaceSeo')
);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
