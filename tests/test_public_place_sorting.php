<?php
/**
 * Test: homepage/public listing should prioritize latest published visits.
 * Run: php tests/test_public_place_sorting.php
 */

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

$places = [
    [
        'slug' => 'ornviken',
        'is_featured' => 0,
        'latest_visit_date' => '2026-04-07',
        'latest_visit_created_at' => '2026-04-07 12:00:00',
        'updated_at' => '2026-04-08 09:00:00',
    ],
    [
        'slug' => 'nyast',
        'is_featured' => 0,
        'latest_visit_date' => '2026-04-09',
        'latest_visit_created_at' => '2026-04-09 08:00:00',
        'updated_at' => '2026-04-07 09:00:00',
    ],
    [
        'slug' => 'featured-old',
        'is_featured' => 1,
        'latest_visit_date' => '2026-03-01',
        'latest_visit_created_at' => '2026-03-01 08:00:00',
        'updated_at' => '2026-03-01 09:00:00',
    ],
];

usort($places, static function (array $a, array $b): int {
    $comparisons = [
        $b['is_featured'] <=> $a['is_featured'],
        strcmp((string) $b['latest_visit_date'], (string) $a['latest_visit_date']),
        strcmp((string) $b['latest_visit_created_at'], (string) $a['latest_visit_created_at']),
        strcmp((string) $b['updated_at'], (string) $a['updated_at']),
    ];

    foreach ($comparisons as $comparison) {
        if ($comparison !== 0) {
            return $comparison;
        }
    }

    return 0;
});

check('Featured place still sorts first', $places[0]['slug'] === 'featured-old');
check('Newest visit sorts ahead of older visit', $places[1]['slug'] === 'nyast');
check('Older visit sorts after newer one', $places[2]['slug'] === 'ornviken');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
