<?php
/**
 * Test: List item state transitions and ordering logic.
 * Run: php tests/test_list_items.php
 */

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Test 1: Toggle done state
function simulateToggle(bool $isDone): array {
    $newDone = !$isDone;
    $doneAt = $newDone ? date('Y-m-d H:i:s') : null;
    return ['is_done' => $newDone, 'done_at' => $doneAt];
}

$result = simulateToggle(false);
check('Toggle undone → done', $result['is_done'] === true);
check('Toggle undone → done sets done_at', $result['done_at'] !== null);

$result = simulateToggle(true);
check('Toggle done → undone', $result['is_done'] === false);
check('Toggle done → undone clears done_at', $result['done_at'] === null);

// Test 2: Item reorder
function simulateItemReorder(array $itemIds): array {
    $result = [];
    foreach ($itemIds as $order => $id) {
        $result[$id] = $order + 1;
    }
    return $result;
}

$result = simulateItemReorder([5, 3, 1, 4, 2]);
check('Reorder: item 5 is first', $result[5] === 1);
check('Reorder: item 2 is last', $result[2] === 5);

// Test 3: Remove and renumber
function simulateRemoveItem(array $items, int $removeId): array {
    $remaining = array_values(array_filter($items, fn($i) => $i['id'] !== $removeId));
    $result = [];
    foreach ($remaining as $i => $item) {
        $result[$item['id']] = $i + 1;
    }
    return $result;
}

$items = [
    ['id' => 10, 'order' => 1],
    ['id' => 20, 'order' => 2],
    ['id' => 30, 'order' => 3],
    ['id' => 40, 'order' => 4],
];
$result = simulateRemoveItem($items, 20);
check('Remove item 20: item 10 stays 1', $result[10] === 1);
check('Remove item 20: item 30 becomes 2', $result[30] === 2);
check('Remove item 20: item 40 becomes 3', $result[40] === 3);
check('Remove item 20: 3 items remain', count($result) === 3);

// Test 4: Sort done items last
function simulateSortDoneLast(array $items): array {
    usort($items, function($a, $b) {
        if ($a['is_done'] !== $b['is_done']) return $a['is_done'] - $b['is_done'];
        return $a['order'] - $b['order'];
    });
    return array_column($items, 'id');
}

$items = [
    ['id' => 1, 'is_done' => 1, 'order' => 1],
    ['id' => 2, 'is_done' => 0, 'order' => 2],
    ['id' => 3, 'is_done' => 0, 'order' => 3],
    ['id' => 4, 'is_done' => 1, 'order' => 4],
];
$sorted = simulateSortDoneLast($items);
check('Sort done last: undone items first', $sorted[0] === 2);
check('Sort done last: second undone', $sorted[1] === 3);
check('Sort done last: first done', $sorted[2] === 1);
check('Sort done last: second done', $sorted[3] === 4);

// Test 5: Template instantiation (JSON parsing)
$templateJson = '[{"text":"Passhållare","category":"Dokument"},{"text":"Laddkablar","category":null},{"text":"Solglasögon","category":"Kläder"}]';
$items = json_decode($templateJson, true);
check('Template JSON: parses 3 items', count($items) === 3);
check('Template JSON: first item text', $items[0]['text'] === 'Passhållare');
check('Template JSON: first item category', $items[0]['category'] === 'Dokument');
check('Template JSON: second item null category', $items[1]['category'] === null);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
