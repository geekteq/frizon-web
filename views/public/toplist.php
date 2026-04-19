<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
?>

<div style="max-width:720px; margin:0 auto; padding:var(--space-6) var(--space-4);">
    <nav class="pub-detail__breadcrumb" aria-label="Brödsmulor" style="margin-bottom:var(--space-3);">
        <a href="/">Platser</a>
        <span aria-hidden="true">›</span>
        <span>Topplista</span>
    </nav>
    <div style="text-align:center; margin-bottom:var(--space-6);">
        <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-2);">Topplistan</h1>
        <p style="color:var(--color-text-muted);">Våra bästa platser, handplockade.</p>
    </div>

    <?php if (empty($places)): ?>
        <p style="text-align:center; color:var(--color-text-muted); font-style:italic; padding:var(--space-8) 0;">Topplistan är tom just nu — vi jobbar på det!</p>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:var(--space-3);">
            <?php foreach ($places as $i => $p): ?>
                <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" style="display:flex; align-items:center; gap:var(--space-4); padding:var(--space-4); background:var(--color-white); border:1px solid var(--color-border); border-radius:var(--radius-lg); text-decoration:none; transition:box-shadow 150ms ease-out;">
                    <span style="width:40px; height:40px; border-radius:50%; background:var(--color-accent); color:var(--color-white); display:flex; align-items:center; justify-content:center; font-weight:var(--weight-bold); font-size:var(--text-lg); flex-shrink:0;"><?= $i + 1 ?></span>
                    <div style="flex:1; min-width:0;">
                        <span style="font-weight:var(--weight-semibold); color:var(--color-text); display:block; margin-bottom:2px;"><?= htmlspecialchars($p['name']) ?></span>
                        <span style="font-size:var(--text-sm); color:var(--color-text-muted);">
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
