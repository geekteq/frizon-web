<?php
/**
 * Test: public templates avoid inline affiliate handlers and nonce JSON-LD.
 * Run: php tests/test_csp_markup.php
 */

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

$shopCard = file_get_contents(dirname(__DIR__) . '/views/partials/shop-card.php');
$shopProduct = file_get_contents(dirname(__DIR__) . '/views/public/shop-product.php');
$placeDetail = file_get_contents(dirname(__DIR__) . '/views/public/place-detail.php');
$publicLayout = file_get_contents(dirname(__DIR__) . '/views/layouts/public.php');

check('Shop card has no inline onclick', strpos($shopCard, 'onclick=') === false);
check('Shop product has no inline onclick', strpos($shopProduct, 'onclick=') === false);
check('Place detail has no inline onclick', strpos($placeDetail, 'onclick=') === false);
check('Shop card uses affiliate data attributes', strpos($shopCard, 'data-affiliate-click="1"') !== false);
check('Shop product uses affiliate data attributes', strpos($shopProduct, 'data-affiliate-click="1"') !== false);
check('Place detail uses affiliate data attributes', strpos($placeDetail, 'data-affiliate-click="1"') !== false);
check('Public layout JSON-LD script uses CSP nonce', strpos($publicLayout, 'type="application/ld+json"<?= app_csp_nonce_attr() ?>') !== false);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
