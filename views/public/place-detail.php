<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
$typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];
?>

<article class="pub-detail">
    <div class="pub-detail__header">
        <a href="/" class="pub-detail__back">&larr; Alla platser</a>
        <h1 class="pub-detail__title"><?= htmlspecialchars($place['name']) ?></h1>
        <div class="pub-detail__meta">
            <span><?= $typeLabel ?></span>
            <?php if ($place['country_code']): ?>
                · <span><?= strtoupper($place['country_code']) ?></span>
            <?php endif; ?>
            <?php if ($avgRating): ?>
                · <span>&#9733; <?= number_format((float)$avgRating, 1) ?></span>
            <?php endif; ?>
            <?php if ($place['is_toplisted']): ?>
                · <span class="pub-detail__toplist">Topplistan</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Map -->
    <div id="place-map" class="pub-detail__map"
         data-lat="<?= $place['lat'] ?>" data-lng="<?= $place['lng'] ?>" data-name="<?= htmlspecialchars($place['name']) ?>">
    </div>

    <!-- Description -->
    <?php if ($place['default_public_text']): ?>
        <div class="pub-detail__text">
            <?= nl2br(htmlspecialchars($place['default_public_text'])) ?>
        </div>
    <?php endif; ?>

    <!-- Images -->
    <?php if (!empty($images)): ?>
        <div class="pub-detail__gallery">
            <?php foreach ($images as $img): ?>
                <button type="button" class="pub-detail__img-btn"
                        data-lightbox
                        data-lightbox-src="/uploads/detail/<?= htmlspecialchars($img['filename']) ?>"
                        data-lightbox-caption="<?= htmlspecialchars($img['alt_text'] ?? '') ?>">
                    <img src="/uploads/detail/<?= htmlspecialchars($img['filename']) ?>"
                         alt="<?= htmlspecialchars($img['alt_text'] ?? $place['name']) ?>"
                         class="pub-detail__img"
                         width="1200" height="900"
                         loading="lazy">
                </button>
            <?php endforeach; ?>
        </div>
        <script src="/js/lightbox.js" defer></script>
    <?php endif; ?>

    <!-- Tags -->
    <?php if (!empty($tags)): ?>
        <div class="pub-detail__tags">
            <?php foreach ($tags as $tag): ?>
                <span class="tag-chip"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if (!empty($faqItems)): ?>
        <section class="pub-detail__faq">
            <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4);">Vanliga frågor</h2>
            <dl style="display:flex; flex-direction:column; gap:var(--space-4);">
                <?php foreach ($faqItems as $item): ?>
                    <div>
                        <dt style="font-weight:var(--weight-semibold); color:var(--color-text); margin-bottom:var(--space-1);"><?= htmlspecialchars($item['q']) ?></dt>
                        <dd style="color:var(--color-text-muted); margin:0; line-height:var(--leading-relaxed);"><?= nl2br(htmlspecialchars($item['a'])) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    <?php endif; ?>

    <!-- Visit summaries -->
    <?php if (!empty($visits)): ?>
        <div class="pub-detail__visits">
            <h3>Besök</h3>
            <?php foreach ($visits as $visit): ?>
                <div class="pub-visit-card">
                    <div class="pub-visit-card__date"><?= htmlspecialchars($visit['visited_at']) ?></div>
                    <?php if ($visit['approved_public_text']): ?>
                        <p><?= nl2br(htmlspecialchars($visit['approved_public_text'])) ?></p>
                    <?php endif; ?>
                    <?php if ($visit['total_rating_cached']): ?>
                        <span class="pub-visit-card__rating">&#9733; <?= number_format((float)$visit['total_rating_cached'], 1) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('place-map');
    if (!mapEl) return;
    var lat = parseFloat(mapEl.dataset.lat);
    var lng = parseFloat(mapEl.dataset.lng);
    var map = L.map(mapEl).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 19
    }).addTo(map);
    var title = document.createElement('strong');
    title.textContent = mapEl.dataset.name;
    L.marker([lat, lng]).addTo(map).bindPopup(title);
});
</script>
