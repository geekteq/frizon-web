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
    <style>
    .place-prod-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--space-3);}
    @media(min-width:640px){.place-prod-grid{grid-template-columns:repeat(4,1fr);}}
    .place-prod-card{display:flex;flex-direction:column;border:1px solid var(--color-border);border-radius:var(--radius-md);text-decoration:none;color:inherit;background:var(--color-bg);overflow:hidden;transition:box-shadow .15s;}
    .place-prod-card:hover{box-shadow:0 2px 8px rgba(0,0,0,.08);}
    .place-prod-card__img{height:110px;display:flex;align-items:center;justify-content:center;background:#f5f5f4;padding:var(--space-3);}
    .place-prod-card__img img{max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;}
    .place-prod-card__body{padding:var(--space-2) var(--space-2) var(--space-3);display:flex;flex-direction:column;gap:2px;flex:1;}
    .place-prod-card__title{font-size:var(--text-xs);font-weight:var(--weight-semibold);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .place-prod-card__cat{font-size:var(--text-xs);color:var(--color-text-muted);}
    .place-prod-card__cta{margin-top:auto;padding-top:var(--space-2);font-size:var(--text-xs);color:var(--color-text-muted);}
    </style>
    <section style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
            Produkter vi använde här
        </h2>
        <div class="place-prod-grid">
            <?php foreach ($placeProducts as $prod): ?>
            <a href="/go/<?= htmlspecialchars($prod['slug']) ?>"
               target="_blank" rel="noopener sponsored"
               class="place-prod-card"
               onclick="typeof gtag!=='undefined'&&gtag('event','affiliate_click',{'product_slug':'<?= htmlspecialchars($prod['slug'], ENT_QUOTES) ?>','product_name':'<?= htmlspecialchars($prod['title'], ENT_QUOTES) ?>','source':'place_detail'})">
                <div class="place-prod-card__img">
                    <?php if ($prod['image_path']): ?>
                        <img src="/uploads/amazon/<?= htmlspecialchars($prod['image_path']) ?>"
                             alt="<?= htmlspecialchars($prod['title']) ?>"
                             width="120" height="120"
                             loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="place-prod-card__body">
                    <div class="place-prod-card__title"><?= htmlspecialchars($prod['title']) ?></div>
                    <?php if ($prod['category']): ?>
                        <div class="place-prod-card__cat"><?= htmlspecialchars($prod['category']) ?></div>
                    <?php endif; ?>
                    <div class="place-prod-card__cta">Se hos Amazon ↗</div>
                </div>
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
