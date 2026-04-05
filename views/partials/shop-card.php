<?php
/* Shop product card — included from shop.php / shop-product.php with $p in scope */
?>
<div class="pub-place-card shop-card">
    <?php if ($p['is_featured']): ?>
        <span class="pub-place-card__featured">★ Utvald</span>
    <?php endif; ?>

    <?php if ($p['image_path']): ?>
        <a href="/shop/<?= htmlspecialchars($p['slug']) ?>" style="display:block; text-decoration:none;">
            <div class="shop-card__img-wrap">
                <img src="/uploads/amazon/<?= htmlspecialchars($p['image_path']) ?>"
                     alt="<?= htmlspecialchars($p['title']) ?>"
                     width="300" height="160"
                     loading="<?= ($shopCardIndex ?? 1) === 0 ? 'eager' : 'lazy' ?>"
                     <?php if (($shopCardIndex ?? 1) === 0): ?> fetchpriority="high"<?php endif; ?>
                     style="width:100%; height:160px; object-fit:contain; background:#fff; padding:var(--space-2);">
            </div>
        </a>
    <?php endif; ?>

    <div class="pub-place-card__body">
        <?php if ($p['category']): ?>
            <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:var(--space-1);">
                <?= htmlspecialchars($p['category']) ?>
            </div>
        <?php endif; ?>

        <a href="/shop/<?= htmlspecialchars($p['slug']) ?>" style="text-decoration:none; color:inherit; display:block; padding:var(--space-2) 0;">
            <h3 class="pub-place-card__name"><?= htmlspecialchars($p['title']) ?></h3>
        </a>

        <?php if ($p['our_description']): ?>
            <p class="pub-place-card__desc"><?= htmlspecialchars(mb_strimwidth($p['our_description'], 0, 100, '...')) ?></p>
        <?php endif; ?>

        <div style="display:flex; gap:var(--space-2); margin-top:var(--space-3);">
            <a href="/shop/<?= htmlspecialchars($p['slug']) ?>"
               class="btn btn-secondary btn--sm"
               style="flex:1; text-align:center;"
               aria-label="Läs mer om <?= htmlspecialchars($p['title']) ?>">Läs mer</a>
            <a href="/go/<?= htmlspecialchars($p['slug']) ?>"
               target="_blank" rel="noopener sponsored"
               class="btn btn--sm"
               style="flex:1; text-align:center; background:#FF9900; color:#111; border:1px solid #e68a00; font-weight:var(--weight-semibold);">
                Se hos Amazon ↗
            </a>
        </div>
    </div>
</div>
