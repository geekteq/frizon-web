<?php
$stopTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
$typeLabel = $stop['stop_type'] ? ($stopTypes[$stop['stop_type']] ?? $stop['stop_type']) : '';
?>
<div class="stop-card" data-stop-id="<?= $stop['id'] ?>">
    <div class="stop-card__order"><?= $stop['stop_order'] ?></div>
    <div class="stop-card__body">
        <div class="stop-card__header">
            <a href="/adm/platser/<?= htmlspecialchars($stop['place_slug']) ?>" class="stop-card__name"><?= htmlspecialchars($stop['place_name']) ?></a>
            <?php if ($typeLabel): ?>
                <span class="stop-card__type"><?= htmlspecialchars($typeLabel) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($stop['note']): ?>
            <p class="stop-card__note"><?= htmlspecialchars($stop['note']) ?></p>
        <?php endif; ?>
    </div>
    <form method="POST" action="/adm/resor/hallplatser/<?= $stop['id'] ?>" class="stop-card__actions">
        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="btn-ghost btn--sm" onclick="return confirm('Ta bort platsen från resan?')" aria-label="Ta bort">×</button>
    </form>
</div>
