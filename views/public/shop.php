<?php
/* Public shop listing — category filter chips + product grid */
?>

<!-- Shop header -->
<div style="max-width:680px; margin:0 auto; padding:var(--space-8) var(--space-4) var(--space-4); text-align:center;">
    <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-3); color:var(--color-text);">Våra favoritprodukter</h1>
    <p style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text-muted); margin-bottom:var(--space-4);">
        Saker vi faktiskt använder på resan med Frizze — noggrant utvalda och personligen testade.
    </p>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); background:var(--color-bg-muted,#f5f5f4); padding:var(--space-3) var(--space-4); border-radius:var(--radius-md); border-left:3px solid var(--color-border);">
        Vi kan tjäna provision på köp via våra länkar — vi rekommenderar bara saker vi själva använder och gillar.
    </p>
</div>

<?php if (!empty($categories)): ?>
<!-- Category filter chips -->
<div style="max-width:var(--content-max-width); margin:0 auto; padding:0 var(--space-4) var(--space-4);">
    <div style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
        <a href="/shop"
           style="display:inline-block; padding:var(--space-1) var(--space-3); border-radius:var(--radius-full,9999px); border:1px solid var(--color-border); font-size:var(--text-sm); text-decoration:none; color:<?= !$filterCategory ? 'var(--color-bg)' : 'var(--color-text)' ?>; background:<?= !$filterCategory ? 'var(--color-text)' : 'transparent' ?>;">
            Alla
        </a>
        <?php foreach ($categories as $cat): ?>
            <?php $isActive = $filterCategory === $cat; ?>
            <a href="/shop?kategori=<?= urlencode($cat) ?>"
               style="display:inline-block; padding:var(--space-1) var(--space-3); border-radius:var(--radius-full,9999px); border:1px solid var(--color-border); font-size:var(--text-sm); text-decoration:none; color:<?= $isActive ? 'var(--color-bg)' : 'var(--color-text)' ?>; background:<?= $isActive ? 'var(--color-text)' : 'transparent' ?>;">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($products)): ?>

<?php
$featured = array_values(array_filter($products, fn($p) => $p['is_featured']));
$regular  = array_values(array_filter($products, fn($p) => !$p['is_featured']));
?>

<?php if (!empty($featured) && !$filterCategory): ?>
<section style="max-width:var(--content-max-width); margin:0 auto var(--space-6); padding:0 var(--space-4);">
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">★ Utvalda favoriter</h2>
    <div class="place-grid">
        <?php $shopCardIndex = 0; foreach ($featured as $p): ?>
            <?php include dirname(__DIR__) . '/partials/shop-card.php'; $shopCardIndex++; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section style="max-width:var(--content-max-width); margin:0 auto; padding:0 var(--space-4) var(--space-8);">
    <?php if (!empty($featured) && !$filterCategory): ?>
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">Alla produkter</h2>
    <?php elseif ($filterCategory): ?>
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);"><?= htmlspecialchars($filterCategory) ?></h2>
    <?php endif; ?>

    <?php $allVisible = $filterCategory ? $products : $regular; ?>
    <div class="place-grid" id="shop-grid">
        <?php if (!isset($shopCardIndex)) $shopCardIndex = 0; foreach ($allVisible as $p): ?>
            <?php include dirname(__DIR__) . '/partials/shop-card.php'; $shopCardIndex++; ?>
        <?php endforeach; ?>
    </div>
    <p id="no-results" style="display:none; color:var(--color-text-muted); font-style:italic; padding:var(--space-4) 0;">Inga produkter matchar sökningen.</p>
</section>

<?php else: ?>
<p style="text-align:center; padding:var(--space-8) var(--space-4); color:var(--color-text-muted); font-style:italic;">
    Inga produkter just nu — kom tillbaka snart!
</p>
<?php endif; ?>
