<?php
$statusLabels = ['planned' => 'Planerad', 'ongoing' => 'Pågående', 'finished' => 'Avslutad'];
$statusLabel = $statusLabels[$trip['status']] ?? $trip['status'];
$stopCount = $trip['stop_count'] ?? 0;
$totalKm = $trip['total_km'] ? number_format((float) $trip['total_km'], 0) : '–';
?>
<a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>" class="trip-card">
    <div class="trip-card__header">
        <span class="trip-card__title"><?= htmlspecialchars($trip['title']) ?></span>
        <span class="trip-card__status trip-card__status--<?= htmlspecialchars($trip['status']) ?>"><?= $statusLabel ?></span>
    </div>
    <div class="trip-card__meta">
        <?php if ($trip['start_date']): ?>
            <span><?= htmlspecialchars($trip['start_date']) ?></span>
            <?php if ($trip['end_date']): ?> → <span><?= htmlspecialchars($trip['end_date']) ?></span><?php endif; ?>
            <span class="trip-card__sep">·</span>
        <?php endif; ?>
        <span><?= $stopCount ?> hållplatser</span>
        <span class="trip-card__sep">·</span>
        <span><?= $totalKm ?> km</span>
    </div>
</a>
