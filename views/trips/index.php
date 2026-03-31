<?php
$statusLabels = ['ongoing' => 'Pågående', 'planned' => 'Planerade', 'finished' => 'Avslutade'];
$hasTrips = !empty($grouped['ongoing']) || !empty($grouped['planned']) || !empty($grouped['finished']);
?>

<div class="flex-between mb-4">
    <h2>Resor</h2>
    <a href="/adm/resor/ny" class="btn btn-primary btn--sm">+ Ny resa</a>
</div>

<?php if (!$hasTrips): ?>
    <div class="empty-state">
        <p class="text-muted">Inga resor ännu.</p>
        <a href="/adm/resor/ny" class="btn btn-primary mt-4">Skapa din första resa</a>
    </div>
<?php else: ?>
    <?php foreach (['ongoing', 'planned', 'finished'] as $status): ?>
        <?php if (!empty($grouped[$status])): ?>
            <div class="status-group status-group--<?= $status ?> mb-6">
                <div class="status-group__header">
                    <span class="status-group__label"><?= $statusLabels[$status] ?></span>
                    <span class="status-group__accent"></span>
                </div>
                <?php foreach ($grouped[$status] as $trip): ?>
                    <?php include dirname(__DIR__) . '/partials/trip-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
