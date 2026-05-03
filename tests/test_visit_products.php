<?php
/**
 * Test: VisitProduct model — sync, find, find-by-many semantics.
 *
 * Run: php tests/test_visit_products.php
 *
 * Requires: a working DB connection via .env (loaded by bootstrap).
 * SKIPs cleanly if no test fixtures (visits / amazon_products) exist.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/Models/VisitProduct.php';

global $pdo;
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "No PDO connection — check .env\n");
    exit(2);
}

$passed = 0;
$failed = 0;
function check(string $name, bool $cond): void {
    global $passed, $failed;
    if ($cond) { echo "PASS: {$name}\n"; $passed++; }
    else        { echo "FAIL: {$name}\n"; $failed++; }
}

// Pick an arbitrary visit + two product IDs that exist
$visitId   = (int) $pdo->query('SELECT id FROM visits LIMIT 1')->fetchColumn();
$productRows = $pdo->query('SELECT id FROM amazon_products LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);

if ($visitId === 0 || count($productRows) < 2) {
    echo "SKIP: Need at least 1 visit and 2 amazon_products in dev DB to run this test.\n";
    exit(0);
}

$model = new VisitProduct($pdo);

// Clean state
$model->syncForVisit($visitId, []);
check('Empty state after clear', count($model->findByVisit($visitId)) === 0);

// Sync two products
$model->syncForVisit($visitId, [(int) $productRows[0], (int) $productRows[1]]);
$got = $model->findByVisit($visitId);
check('Two products attached', count($got) === 2);

// Re-sync with one — second should be removed
$model->syncForVisit($visitId, [(int) $productRows[0]]);
$got = $model->findByVisit($visitId);
check('Re-sync narrows to one product', count($got) === 1);
check('Remaining product is the first one', (int) $got[0]['id'] === (int) $productRows[0]);

// Test findByVisitIds returns grouped result
$grouped = $model->findByVisitIds([$visitId]);
check('findByVisitIds returns grouped array', isset($grouped[$visitId]) && count($grouped[$visitId]) === 1);

// Cleanup
$model->syncForVisit($visitId, []);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
