<?php
/* Shop product card — included from shop.php with $p in scope */
?>
<a href="/shop/<?= htmlspecialchars($p['slug']) ?>"
   class="pub-place-card shop-card"
   data-title="<?= htmlspecialchars($p['title']) ?>">
    <?php if ($p['is_featured']): ?>
        <span class="pub-place-card__featured">★ Utvald</span>
    <?php endif; ?>
    <?php if ($p['image_path']): ?>
        <div class="shop-card__img-wrap">
            <img src="/uploads/amazon/<?= htmlspecialchars($p['image_path']) ?>"
                 alt="<?= htmlspecialchars($p['title']) ?>"
                 loading="lazy"
                 style="width:100%; height:160px; object-fit:contain; background:#fff; padding:var(--space-2);">
        </div>
    <?php endif; ?>
    <div class="pub-place-card__body">
        <?php if ($p['category']): ?>
            <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:var(--space-1);">
                <?= htmlspecialchars($p['category']) ?>
            </div>
        <?php endif; ?>
        <h3 class="pub-place-card__name"><?= htmlspecialchars($p['title']) ?></h3>
        <?php if ($p['our_description']): ?>
            <p class="pub-place-card__desc"><?= htmlspecialchars(mb_strimwidth($p['our_description'], 0, 100, '...')) ?></p>
        <?php endif; ?>
        <div style="margin-top:var(--space-3);">
            <span class="btn btn-primary btn--sm" style="pointer-events:none;">Se hos Amazon →</span>
        </div>
    </div>
</a>
