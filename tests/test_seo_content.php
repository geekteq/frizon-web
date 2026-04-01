<?php
/**
 * Test: SEO content generation (FakeAiProvider).
 * Run: php tests/test_seo_content.php
 */

require_once dirname(__DIR__) . '/app/Services/AiService.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else             { echo "FAIL: {$name}\n"; $failed++; }
}

$place = [
    'name'                => 'Hammarö Ställplats',
    'place_type'          => 'stellplatz',
    'country_code'        => 'SE',
    'default_public_text' => 'Fin ställplats vid sjön.',
    'meta_description'    => null,
    'faq_content'         => null,
];

$visits = [[
    'approved_public_text' => 'Vi stannade en natt och trivdes bra.',
    'suitable_for'         => 'husbilar, hundar',
    'tips_notes'           => 'Boka tidigt på sommaren',
    'price_level'          => 'low',
    'total_rating_cached'  => 4.2,
]];

$_ENV['AI_PROVIDER'] = 'fake';
$service = new AiService();
$result  = $service->generatePlaceSeo($place, $visits);

check('Returns meta_description key',           isset($result['meta_description']));
check('Returns faq_content key',                isset($result['faq_content']));
check('meta_description is non-empty string',   is_string($result['meta_description']) && strlen($result['meta_description']) > 0);
check('meta_description max 155 chars',         mb_strlen($result['meta_description']) <= 155);
check('meta_description contains place name',   str_contains($result['meta_description'], 'Hammarö'));
check('faq_content is valid JSON',              json_decode($result['faq_content']) !== null);
check('faq_content decodes to array',           is_array(json_decode($result['faq_content'], true)));
check('faq has at least one item',              count(json_decode($result['faq_content'], true)) >= 1);

$faq = json_decode($result['faq_content'], true);
check('Each FAQ item has q key',                isset($faq[0]['q']));
check('Each FAQ item has a key',                isset($faq[0]['a']));
check('FAQ q is non-empty',                     strlen($faq[0]['q']) > 0);
check('FAQ a is non-empty',                     strlen($faq[0]['a']) > 0);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
