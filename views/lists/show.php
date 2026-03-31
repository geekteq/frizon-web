<div class="page-header mb-4">
    <a href="/listor" class="btn-ghost btn--sm">&larr; Listor</a>
</div>

<?php
$typeLabels = ['checklist' => 'Checklista', 'shopping' => 'Inköpslista'];
$itemCount = count($items);
$doneCount = count(array_filter($items, fn($i) => $i['is_done']));
?>

<div class="flex-between mb-2">
    <h2><?= htmlspecialchars($list['title']) ?></h2>
    <a href="/listor/<?= $list['id'] ?>/redigera" class="btn btn-ghost btn--sm">Redigera</a>
</div>

<div class="text-sm text-muted mb-4">
    <?= $typeLabels[$list['list_type']] ?? $list['list_type'] ?>
    · <?= $doneCount ?>/<?= $itemCount ?> klara
</div>

<!-- Items -->
<div class="checklist" id="checklist" data-list-id="<?= $list['id'] ?>">
    <?php if (empty($items)): ?>
        <p class="text-muted text-sm">Inga punkter ännu. Lägg till nedan.</p>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="checklist-item <?= $item['is_done'] ? 'checklist-item--done' : '' ?>"
                 data-item-id="<?= $item['id'] ?>">
                <button class="checklist-item__check" aria-label="<?= $item['is_done'] ? 'Markera ej klar' : 'Markera klar' ?>">
                    <span class="checklist-item__checkbox <?= $item['is_done'] ? 'checklist-item__checkbox--checked' : '' ?>"></span>
                </button>
                <span class="checklist-item__text"><?= htmlspecialchars($item['text']) ?></span>
                <?php if ($item['category']): ?>
                    <span class="checklist-item__category"><?= htmlspecialchars($item['category']) ?></span>
                <?php endif; ?>
                <form method="POST" action="/listor/punkt/<?= $item['id'] ?>" class="checklist-item__delete">
                    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn-ghost btn--sm" aria-label="Ta bort">×</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add item form -->
<form method="POST" action="/listor/<?= $list['id'] ?>/punkt" class="mt-4">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <div class="flex gap-2">
        <input type="text" name="text" class="form-input" required placeholder="Ny punkt..." style="flex:1;">
        <button type="submit" class="btn btn-primary btn--sm">+</button>
    </div>
</form>

<script src="/js/lists.js"></script>
