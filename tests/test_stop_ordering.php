<?php
/**
 * Test: Stop ordering logic (unit test without database).
 * Run: php tests/test_stop_ordering.php
 */

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Simulate reorder: given stop_ids in new order, verify order assignment
function simulateReorder(array $stopIds): array {
    $result = [];
    foreach ($stopIds as $order => $id) {
        $result[$id] = $order + 1;
    }
    return $result;
}

// Test 1: Simple reorder
$result = simulateReorder([3, 1, 2]);
check('Reorder [3,1,2]: stop 3 is first', $result[3] === 1);
check('Reorder [3,1,2]: stop 1 is second', $result[1] === 2);
check('Reorder [3,1,2]: stop 2 is third', $result[2] === 3);

// Test 2: No change
$result = simulateReorder([1, 2, 3]);
check('No change: stop 1 stays first', $result[1] === 1);
check('No change: stop 3 stays third', $result[3] === 3);

// Test 3: Reverse
$result = simulateReorder([5, 4, 3, 2, 1]);
check('Reverse: stop 5 is first', $result[5] === 1);
check('Reverse: stop 1 is last', $result[1] === 5);

// Test 4: Single stop
$result = simulateReorder([42]);
check('Single stop: order is 1', $result[42] === 1);

// Test 5: After removal, renumbering
function simulateRemoveAndRenumber(array $stops, int $removeId): array {
    $remaining = array_values(array_filter($stops, fn($s) => $s['id'] !== $removeId));
    $result = [];
    foreach ($remaining as $i => $stop) {
        $result[$stop['id']] = $i + 1;
    }
    return $result;
}

$stops = [
    ['id' => 1, 'order' => 1],
    ['id' => 2, 'order' => 2],
    ['id' => 3, 'order' => 3],
];
$result = simulateRemoveAndRenumber($stops, 2);
check('Remove middle: stop 1 stays 1', $result[1] === 1);
check('Remove middle: stop 3 becomes 2', $result[3] === 2);
check('Remove middle: only 2 stops remain', count($result) === 2);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
