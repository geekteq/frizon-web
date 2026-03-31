<div class="flex-between mb-4">
    <h2>Listor</h2>
    <div class="flex gap-2">
        <a href="/adm/listor/mallar" class="btn btn-ghost btn--sm">Mallar</a>
        <a href="/adm/listor/ny" class="btn btn-primary btn--sm">+ Ny lista</a>
    </div>
</div>

<?php
$hasLists = !empty($grouped['checklist']) || !empty($grouped['shopping']);
$typeLabels = ['checklist' => 'Checklistor', 'shopping' => 'Inköpslistor'];
?>

<?php if (!$hasLists): ?>
    <div class="empty-state">
        <p class="text-muted">Inga listor ännu.</p>
        <a href="/adm/listor/ny" class="btn btn-primary mt-4">Skapa din första lista</a>
    </div>
<?php else: ?>
    <?php foreach (['checklist', 'shopping'] as $type): ?>
        <?php if (!empty($grouped[$type])): ?>
            <div class="mb-6">
                <h3 class="mb-3"><?= $typeLabels[$type] ?></h3>
                <?php foreach ($grouped[$type] as $list): ?>
                    <?php
                    $itemCount = (int) ($list['item_count'] ?? 0);
                    $doneCount = (int) ($list['done_count'] ?? 0);
                    $pct = $itemCount > 0 ? round($doneCount / $itemCount * 100) : 0;
                    ?>
                    <a href="/adm/listor/<?= $list['id'] ?>" class="list-card">
                        <div class="list-card__header">
                            <span class="list-card__title"><?= htmlspecialchars($list['title']) ?></span>
                            <span class="list-card__count"><?= $doneCount ?>/<?= $itemCount ?></span>
                        </div>
                        <?php if ($itemCount > 0): ?>
                            <div class="list-card__progress">
                                <div class="list-card__progress-bar" style="width:<?= $pct ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
