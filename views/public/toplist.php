<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Vildcamping','camping'=>'Camping',
];
?>

<div class="pub-toplist">
    <h1 class="mb-2">Topplistan</h1>
    <p class="text-muted mb-6">Våra bästa platser, handplockade.</p>

    <?php if (empty($places)): ?>
        <p class="text-muted">Topplistan är tom just nu.</p>
    <?php else: ?>
        <div class="pub-toplist__list">
            <?php foreach ($places as $i => $p): ?>
                <a href="/pub/platser/<?= htmlspecialchars($p['slug']) ?>" class="pub-toplist__item">
                    <span class="pub-toplist__rank"><?= $i + 1 ?></span>
                    <div class="pub-toplist__body">
                        <span class="pub-toplist__name"><?= htmlspecialchars($p['name']) ?></span>
                        <span class="pub-toplist__meta">
                            <?= $placeTypes[$p['place_type']] ?? $p['place_type'] ?>
                            <?php if ($p['country_code']): ?> · <?= strtoupper($p['country_code']) ?><?php endif; ?>
                            <?php if ($p['avg_rating']): ?> · &#9733; <?= number_format((float)$p['avg_rating'], 1) ?><?php endif; ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
