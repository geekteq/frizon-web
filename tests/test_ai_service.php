<?php
/**
 * Test: AI service (FakeAiProvider).
 * Run: php tests/test_ai_service.php
 */

require_once dirname(__DIR__) . '/app/Services/AiService.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Test FakeAiProvider
$provider = new FakeAiProvider();
$context = [
    'place_name' => 'Hammarö Ställplats',
    'place_type' => 'stellplatz',
    'raw_note' => 'Fin ställplats vid sjön. Lugnt och tyst.',
    'plus_notes' => 'Nära vatten, bra servicehus',
    'minus_notes' => 'Lite trångt',
    'tips_notes' => 'Boka tidigt på sommaren',
    'suitable_for' => 'husbilar, hundar',
    'total_rating' => 4.2,
];

$draft = $provider->generateDraft($context);

check('Draft is non-empty', strlen($draft) > 50);
check('Draft contains place name', str_contains($draft, 'Hammarö Ställplats'));
check('Draft is in Swedish (contains common word)', str_contains($draft, 'plats') || str_contains($draft, 'ställ') || str_contains($draft, 'besök'));
check('Draft mentions plus notes', str_contains($draft, 'vatten') || str_contains($draft, 'bra'));

// Test AiService defaults to fake
$_ENV['AI_PROVIDER'] = 'fake';
$service = new AiService();
$serviceDraft = $service->generateDraft($context);
check('AiService returns non-empty draft', strlen($serviceDraft) > 50);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
