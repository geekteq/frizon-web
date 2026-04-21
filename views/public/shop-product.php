<?php
/* Public shop product detail page */
?>
<article style="max-width:720px; margin:0 auto; padding:var(--space-6) var(--space-4) var(--space-10);">

    <!-- Breadcrumb -->
    <nav aria-label="Brödsmulor" style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-6);">
        <a href="/shop" style="color:var(--color-text-muted); text-decoration:underline;">Shop</a>
        <?php if ($product['category']): ?>
            <span style="margin:0 var(--space-2);">›</span>
            <a href="/shop?kategori=<?= urlencode($product['category']) ?>"
               style="color:var(--color-text-muted); text-decoration:underline;"><?= htmlspecialchars($product['category']) ?></a>
        <?php endif; ?>
        <span style="margin:0 var(--space-2);">›</span>
        <span><?= htmlspecialchars($product['title']) ?></span>
    </nav>

    <?php if ($product['image_path']): ?>
    <div style="text-align:center; background:#fff; border-radius:var(--radius-lg); padding:var(--space-6); border:1px solid var(--color-border); margin-bottom:var(--space-6);">
        <img src="/uploads/amazon-detail/<?= htmlspecialchars($product['image_path']) ?>"
             alt="<?= htmlspecialchars($product['title']) ?>"
             loading="eager"
             fetchpriority="high"
             decoding="async"
             style="display:block; margin:0 auto; width:auto; max-width:100%; max-height:340px; object-fit:contain;">
    </div>
    <?php endif; ?>

    <div>
        <?php if ($product['category']): ?>
        <p style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:var(--space-2);">
            <a href="/shop?kategori=<?= urlencode($product['category']) ?>"
               style="color:var(--color-text-muted); text-decoration:none;"><?= htmlspecialchars($product['category']) ?></a>
        </p>
        <?php endif; ?>

        <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-5); line-height:var(--leading-tight);">
            <?= htmlspecialchars($product['title']) ?>
        </h1>

        <?php if ($product['our_description']): ?>
        <div style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text); margin-bottom:var(--space-6);">
            <?php foreach (explode("\n\n", $product['our_description']) as $para): ?>
                <?php if (trim($para) !== ''): ?>
                <p style="margin-bottom:var(--space-3);"><?= htmlspecialchars($para) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <a href="/go/<?= htmlspecialchars($product['slug']) ?>"
           target="_blank" rel="noopener sponsored"
           class="btn btn-primary"
           style="display:inline-block; font-size:var(--text-base); padding:var(--space-3) var(--space-6);"
           data-affiliate-click="1"
           data-affiliate-product-slug="<?= htmlspecialchars($product['slug'], ENT_QUOTES) ?>"
           data-affiliate-product-name="<?= htmlspecialchars($product['title'], ENT_QUOTES) ?>">
            Köp hos Amazon →
        </a>

        <p style="margin-top:var(--space-3); font-size:var(--text-xs); color:var(--color-text-muted);">
            Affiliatelänk — vi kan tjäna provision på köp, utan extra kostnad för dig.
        </p>
    </div>

    <?php if ($product['amazon_description']): ?>
    <div style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">Om produkten</h2>
        <p style="font-size:var(--text-sm); line-height:var(--leading-relaxed); color:var(--color-text-muted);">
            <?= htmlspecialchars($product['amazon_description']) ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if (!empty($related)): ?>
    <div style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4);">
            Fler i <?= htmlspecialchars($product['category']) ?>
        </h2>
        <div class="place-grid">
            <?php foreach ($related as $p): ?>
                <?php include dirname(__DIR__) . '/partials/shop-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
        <a href="/shop" style="color:var(--color-text-muted); font-size:var(--text-sm); text-decoration:underline;">← Tillbaka till shoppen</a>
    </div>

</article>
