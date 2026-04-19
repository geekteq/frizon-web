<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
$typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];

$priceLevels = ['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'];
$wouldReturnLabels = ['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'];
?>

<article class="pub-detail">
    <div class="pub-detail__header">
        <a href="/platser/<?= htmlspecialchars($place['slug']) ?>" class="pub-detail__back">&larr; <?= htmlspecialchars($place['name']) ?></a>
        <h1 class="pub-detail__title"><?= htmlspecialchars($visit['visited_at']) ?></h1>
        <div class="pub-detail__meta">
            <span><?= htmlspecialchars($place['name']) ?></span>
            · <span><?= $typeLabel ?></span>
            <?php if ($visit['total_rating_cached']): ?>
                · <span>&#9733; <?= number_format((float)$visit['total_rating_cached'], 1) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Images grid -->
    <?php if (!empty($images)): ?>
        <div class="pub-visit__images">
            <?php foreach ($images as $i => $img): ?>
                <button type="button"
                        class="pub-visit__img-btn <?= $i === 0 ? 'pub-visit__img-btn--hero' : '' ?>"
                        data-lightbox
                        data-lightbox-src="/uploads/detail/<?= htmlspecialchars($img['filename']) ?>"
                        data-lightbox-caption="<?= htmlspecialchars($img['alt_text'] ?? '') ?>">
                    <img src="/uploads/<?= $i === 0 ? 'detail' : 'cards' ?>/<?= htmlspecialchars($img['filename']) ?>"
                         alt="<?= htmlspecialchars($img['alt_text'] ?? $place['name']) ?>"
                         width="<?= $i === 0 ? '1200' : '400' ?>"
                         height="<?= $i === 0 ? '900' : '300' ?>"
                         loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                </button>
            <?php endforeach; ?>
        </div>
        <script src="/js/lightbox.js" defer></script>
    <?php endif; ?>

    <!-- Visit text -->
    <?php if ($visit['approved_public_text']): ?>
        <div class="pub-detail__text">
            <?= nl2br(htmlspecialchars($visit['approved_public_text'])) ?>
        </div>
    <?php endif; ?>

    <!-- Ratings -->
    <?php if ($visit['total_rating_cached']): ?>
        <div class="pub-visit__ratings">
            <h3 class="pub-visit__section-title">Betyg</h3>
            <div class="pub-visit__rating-grid">
                <?php
                $ratingLabels = [
                    'location_rating' => 'Läge', 'calmness_rating' => 'Lugn',
                    'service_rating' => 'Service', 'value_rating' => 'Prisvärt',
                    'return_value_rating' => 'Återkomst',
                ];
                foreach ($ratingLabels as $field => $label):
                    if (!empty($visit[$field])):
                ?>
                    <div class="pub-visit__rating-item">
                        <span class="pub-visit__rating-label"><?= $label ?></span>
                        <span class="pub-visit__rating-value"><?= (int)$visit[$field] ?>/5</span>
                    </div>
                <?php endif; endforeach; ?>
            </div>
            <div class="pub-visit__rating-avg">
                <span class="pub-visit__rating-label">Snitt</span>
                <span class="pub-visit__rating-value pub-visit__rating-value--avg"><?= number_format((float)$visit['total_rating_cached'], 1) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Details -->
    <?php if ($visit['price_level'] || $visit['would_return'] || $visit['suitable_for']): ?>
        <div class="pub-visit__details">
            <?php if ($visit['price_level']): ?>
                <div class="pub-visit__detail-row">
                    <span class="pub-visit__detail-label">Prisnivå</span>
                    <span><?= htmlspecialchars($priceLevels[$visit['price_level']] ?? $visit['price_level']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($visit['would_return']): ?>
                <div class="pub-visit__detail-row">
                    <span class="pub-visit__detail-label">Skulle återvända</span>
                    <span><?= htmlspecialchars($wouldReturnLabels[$visit['would_return']] ?? $visit['would_return']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($visit['suitable_for']): ?>
                <div class="pub-visit__detail-row">
                    <span class="pub-visit__detail-label">Passar för</span>
                    <span><?= htmlspecialchars($visit['suitable_for']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Back link -->
    <div class="pub-detail__footer">
        <a href="/platser/<?= htmlspecialchars($place['slug']) ?>">&larr; Alla besök på <?= htmlspecialchars($place['name']) ?></a>
    </div>
</article>
