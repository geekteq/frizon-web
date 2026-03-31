<?php
$placeTypes = [
    'breakfast' => 'Frukost', 'lunch' => 'Lunch', 'dinner' => 'Middag',
    'fika' => 'Fika', 'sight' => 'Sevärdhet', 'shopping' => 'Shopping',
    'stellplatz' => 'Ställplats', 'wild_camping' => 'Fricamping', 'camping' => 'Camping',
];
$typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];
$rating = $place['avg_rating'] ?? null;
$visitCount = $place['visit_count'] ?? 0;
?>
<a href="/adm/platser/<?= htmlspecialchars($place['slug']) ?>" class="place-card">
    <div class="place-card__icon place-card__icon--<?= htmlspecialchars($place['place_type']) ?>">
        <span class="place-card__type-badge"><?= htmlspecialchars($typeLabel) ?></span>
    </div>
    <div class="place-card__body">
        <div class="place-card__name"><?= htmlspecialchars($place['name']) ?></div>
        <div class="place-card__meta">
            <?php if ($place['country_code']): ?>
                <?= htmlspecialchars($place['country_code']) ?>
            <?php endif; ?>
            <?php if ($visitCount > 0): ?>
                · <?= $visitCount ?> besök
            <?php endif; ?>
        </div>
    </div>
    <?php if ($rating): ?>
        <div class="place-card__rating">&#9733; <?= number_format((float) $rating, 1) ?></div>
    <?php endif; ?>
</a>
