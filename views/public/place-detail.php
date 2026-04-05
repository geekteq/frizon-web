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

    <?php if (!empty($placeProducts)): ?>
    <section style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
            Produkter vi använde här
        </h2>
        <div style="display:flex; flex-direction:column; gap:var(--space-3);">
            <?php foreach ($placeProducts as $prod): ?>
            <a href="/go/<?= htmlspecialchars($prod['slug']) ?>"
               target="_blank" rel="noopener sponsored"
               onclick="typeof gtag!=='undefined'&&gtag('event','affiliate_click',{'product_slug':'<?= htmlspecialchars($prod['slug'], ENT_QUOTES) ?>','product_name':'<?= htmlspecialchars($prod['title'], ENT_QUOTES) ?>','source':'place_detail'})"
               style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); text-decoration:none; color:inherit; background:var(--color-bg);">
                <?php if ($prod['image_path']): ?>
                    <img src="/uploads/amazon/<?= htmlspecialchars($prod['image_path']) ?>"
                         alt="<?= htmlspecialchars($prod['title']) ?>"
                         width="56" height="56"
                         loading="lazy"
                         style="width:56px; height:56px; object-fit:contain; background:#f5f5f4; border-radius:var(--radius-sm); flex-shrink:0;">
                <?php endif; ?>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:var(--text-sm); font-weight:var(--weight-semibold); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars($prod['title']) ?>
                    </div>
                    <?php if ($prod['category']): ?>
                    <div style="font-size:var(--text-xs); color:var(--color-text-muted); margin-top:2px;">
                        <?= htmlspecialchars($prod['category']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <span style="font-size:var(--text-sm); color:var(--color-text-muted); flex-shrink:0;">Se hos Amazon &#x2197;</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
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
