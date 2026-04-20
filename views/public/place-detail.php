<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
$typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];
?>

<article class="pub-detail">
    <nav class="pub-detail__breadcrumb" aria-label="Brödsmulor">
        <a href="/">Platser</a>
        <span aria-hidden="true">›</span>
        <span><?= htmlspecialchars($place['name']) ?></span>
    </nav>
    <div class="pub-detail__header">
        <h1 class="pub-detail__title"><?= htmlspecialchars($place['name']) ?></h1>
        <div class="pub-detail__meta">
            <span><?= $typeLabel ?></span>
            <?php if ($place['country_code']): ?>
                · <span><?= strtoupper($place['country_code']) ?></span>
            <?php endif; ?>
            <?php if ($avgRating): ?>
                · <span>&#9733; <?= number_format((float)$avgRating, 1) ?></span>
            <?php endif; ?>
            <?php if (count($visits) > 0): ?>
                · <span>Besökt <?= count($visits) ?> <?= count($visits) === 1 ? 'gång' : 'gånger' ?></span>
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

    <!-- Preview image -->
    <?php if (!empty($previewImage)): ?>
        <div class="pub-detail__preview-img">
            <img src="/uploads/detail/<?= htmlspecialchars($previewImage['filename']) ?>"
                 srcset="/uploads/cards/<?= htmlspecialchars($previewImage['filename']) ?> 400w, /uploads/medium/<?= htmlspecialchars($previewImage['filename']) ?> 800w, /uploads/detail/<?= htmlspecialchars($previewImage['filename']) ?> 1200w"
                 sizes="(min-width: 900px) 760px, calc(100vw - 32px)"
                 alt="<?= htmlspecialchars($previewImage['alt_text'] ?? $place['name']) ?>"
                 width="1200" height="900"
                 loading="eager">
        </div>
    <?php endif; ?>

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
                    <img src="/uploads/cards/<?= htmlspecialchars($img['filename']) ?>"
                         srcset="/uploads/cards/<?= htmlspecialchars($img['filename']) ?> 400w, /uploads/medium/<?= htmlspecialchars($img['filename']) ?> 800w, /uploads/detail/<?= htmlspecialchars($img['filename']) ?> 1200w"
                         sizes="(min-width: 900px) 372px, (min-width: 600px) calc((100vw - 48px) / 2), calc(100vw - 32px)"
                         alt="<?= htmlspecialchars($img['alt_text'] ?? $place['name']) ?>"
                         class="pub-detail__img"
                         width="400" height="300"
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
            <!-- Latest visit -->
            <?php $latest = $visits[0]; ?>
            <div class="pub-visit-card__section-title">Senaste besöket</div>
            <a href="/platser/<?= htmlspecialchars($place['slug']) ?>/besok/<?= $latest['id'] ?>" class="pub-visit-card pub-visit-card--linked">
                <div class="pub-visit-card__top">
                    <span class="pub-visit-card__date"><?= htmlspecialchars($latest['visited_at']) ?></span>
                    <span class="pub-visit-card__right">
                        <?php if ($latest['total_rating_cached']): ?>
                            <span class="pub-visit-card__rating">&#9733; <?= number_format((float)$latest['total_rating_cached'], 1) ?></span>
                        <?php endif; ?>
                        <span class="pub-visit-card__chevron">&rsaquo;</span>
                    </span>
                </div>
                <?php if ($latest['approved_public_text']): ?>
                    <p class="pub-visit-card__text"><?= nl2br(htmlspecialchars(mb_strimwidth($latest['approved_public_text'], 0, 250, '...'))) ?></p>
                <?php endif; ?>
                <?php $latestImgCount = $visitImageCounts[$latest['id']] ?? 0; ?>
                <?php if ($latestImgCount > 0): ?>
                    <span class="pub-visit-card__img-count"><?= $latestImgCount ?> <?= $latestImgCount === 1 ? 'bild' : 'bilder' ?></span>
                <?php endif; ?>
            </a>

            <!-- Older visits -->
            <?php if (count($visits) > 1): ?>
                <details class="pub-detail__older-visits">
                    <summary class="pub-detail__older-summary">
                        <span>Tidigare besök (<?= count($visits) - 1 ?>)</span>
                        <span class="pub-detail__older-toggle">Visa</span>
                    </summary>
                    <?php for ($i = 1; $i < count($visits); $i++): $v = $visits[$i]; ?>
                        <a href="/platser/<?= htmlspecialchars($place['slug']) ?>/besok/<?= $v['id'] ?>" class="pub-visit-card pub-visit-card--linked pub-visit-card--compact">
                            <div class="pub-visit-card__top">
                                <span class="pub-visit-card__date"><?= htmlspecialchars($v['visited_at']) ?></span>
                                <span class="pub-visit-card__right">
                                    <?php if ($v['total_rating_cached']): ?>
                                        <span class="pub-visit-card__rating">&#9733; <?= number_format((float)$v['total_rating_cached'], 1) ?></span>
                                    <?php endif; ?>
                                    <span class="pub-visit-card__chevron">&rsaquo;</span>
                                </span>
                            </div>
                            <?php if ($v['approved_public_text']): ?>
                                <p class="pub-visit-card__text"><?= nl2br(htmlspecialchars(mb_strimwidth($v['approved_public_text'], 0, 150, '...'))) ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endfor; ?>
                </details>
            <?php endif; ?>
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
               data-affiliate-click="1"
               data-affiliate-product-slug="<?= htmlspecialchars($prod['slug'], ENT_QUOTES) ?>"
               data-affiliate-product-name="<?= htmlspecialchars($prod['title'], ENT_QUOTES) ?>"
               data-affiliate-source="place_detail">
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

    window.loadFrizonLeaflet().then(function() {
        var map = L.map(mapEl).setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
            detectRetina: true
        }).addTo(map);
        var title = document.createElement('strong');
        title.textContent = mapEl.dataset.name;
        L.marker([lat, lng]).addTo(map).bindPopup(title);
    });
});
</script>
