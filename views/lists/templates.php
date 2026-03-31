<div class="flex-between mb-4">
    <h2>Listmallar</h2>
    <div class="flex gap-2">
        <a href="/adm/listor" class="btn btn-ghost btn--sm">&larr; Listor</a>
        <a href="/adm/listor/mallar/ny" class="btn btn-primary btn--sm">+ Ny mall</a>
    </div>
</div>

<?php if (empty($templates)): ?>
    <div class="empty-state">
        <p class="text-muted">Inga mallar ännu.</p>
        <a href="/adm/listor/mallar/ny" class="btn btn-primary mt-4">Skapa din första mall</a>
    </div>
<?php else: ?>
    <?php foreach ($templates as $t): ?>
        <?php
        $items = json_decode($t['items_json'], true);
        $itemCount = is_array($items) ? count($items) : 0;
        ?>
        <div class="list-card mb-3">
            <div class="list-card__header">
                <span class="list-card__title"><?= htmlspecialchars($t['title']) ?></span>
                <span class="list-card__count"><?= $itemCount ?> punkter</span>
            </div>
            <?php if ($t['description']): ?>
                <p class="text-sm text-muted mt-1"><?= htmlspecialchars($t['description']) ?></p>
            <?php endif; ?>
            <form method="POST" action="/adm/listor/mallar/<?= $t['id'] ?>" class="mt-2">
                <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Ta bort mallen?')">Ta bort</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
