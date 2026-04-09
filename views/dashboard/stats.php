<?php /* Admin statistics — affiliate clicks + place views */ ?>

<div style="padding:var(--space-6) var(--space-4) var(--space-10); max-width:900px;">

    <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-6);">Statistik</h1>

    <!-- Summary row -->
    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:var(--space-4); margin-bottom:var(--space-8);">
        <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:var(--space-5);">
            <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:var(--space-1);">Klick senaste 30 dagarna</div>
            <div style="font-size:var(--text-3xl); font-weight:var(--weight-bold);"><?= number_format($totalClicks30d) ?></div>
        </div>
        <div style="background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:var(--space-5);">
            <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:var(--space-1);">Klick totalt</div>
            <div style="font-size:var(--text-3xl); font-weight:var(--weight-bold);"><?= number_format($totalClicksAllTime) ?></div>
        </div>
    </div>

    <!-- Top products -->
    <section style="margin-bottom:var(--space-8);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">Mest klickade produkter <span style="font-size:var(--text-sm); color:var(--color-text-muted); font-weight:normal;">(30 dagar)</span></h2>
        <?php if (empty($topProducts)): ?>
            <p style="color:var(--color-text-muted); font-size:var(--text-sm);">Inga klick registrerade ännu.</p>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse; font-size:var(--text-sm);">
            <thead>
                <tr style="border-bottom:2px solid var(--color-border); text-align:left;">
                    <th style="padding:var(--space-2) var(--space-3) var(--space-2) 0; color:var(--color-text-muted); font-weight:var(--weight-medium);">#</th>
                    <th style="padding:var(--space-2) var(--space-3); color:var(--color-text-muted); font-weight:var(--weight-medium);">Produkt</th>
                    <th style="padding:var(--space-2) 0 var(--space-2) var(--space-3); color:var(--color-text-muted); font-weight:var(--weight-medium); text-align:right;">Klick</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $i => $row): ?>
                <tr style="border-bottom:1px solid var(--color-border);">
                    <td style="padding:var(--space-2) var(--space-3) var(--space-2) 0; color:var(--color-text-muted);"><?= $i + 1 ?></td>
                    <td style="padding:var(--space-2) var(--space-3);">
                        <a href="/adm/amazon-lista" style="color:inherit; text-decoration:none;"><?= htmlspecialchars($row['title']) ?></a>
                    </td>
                    <td style="padding:var(--space-2) 0 var(--space-2) var(--space-3); text-align:right; font-weight:var(--weight-semibold);"><?= $row['clicks'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- Top referrers -->
    <section style="margin-bottom:var(--space-8);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">Sidor som driver klick <span style="font-size:var(--text-sm); color:var(--color-text-muted); font-weight:normal;">(30 dagar)</span></h2>
        <?php if (empty($topReferrers)): ?>
            <p style="color:var(--color-text-muted); font-size:var(--text-sm);">Ingen data ännu.</p>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse; font-size:var(--text-sm);">
            <thead>
                <tr style="border-bottom:2px solid var(--color-border); text-align:left;">
                    <th style="padding:var(--space-2) var(--space-3) var(--space-2) 0; color:var(--color-text-muted); font-weight:var(--weight-medium);">#</th>
                    <th style="padding:var(--space-2) var(--space-3); color:var(--color-text-muted); font-weight:var(--weight-medium);">Sida</th>
                    <th style="padding:var(--space-2) 0 var(--space-2) var(--space-3); color:var(--color-text-muted); font-weight:var(--weight-medium); text-align:right;">Klick</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topReferrers as $i => $row): ?>
                <tr style="border-bottom:1px solid var(--color-border);">
                    <td style="padding:var(--space-2) var(--space-3) var(--space-2) 0; color:var(--color-text-muted);"><?= $i + 1 ?></td>
                    <td style="padding:var(--space-2) var(--space-3); font-family:monospace; font-size:var(--text-xs);">
                        <?php if (!empty($row['safe_href'])): ?>
                            <a href="<?= htmlspecialchars($row['safe_href']) ?>" target="_blank" rel="noopener noreferrer nofollow" style="color:inherit;"><?= htmlspecialchars($row['display_referrer']) ?></a>
                        <?php else: ?>
                            <span><?= htmlspecialchars($row['display_referrer']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:var(--space-2) 0 var(--space-2) var(--space-3); text-align:right; font-weight:var(--weight-semibold);"><?= $row['clicks'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- Top places by views -->
    <section>
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">Mest besökta platser <span style="font-size:var(--text-sm); color:var(--color-text-muted); font-weight:normal;">(totalt)</span></h2>
        <?php if (empty($topPlaces)): ?>
            <p style="color:var(--color-text-muted); font-size:var(--text-sm);">Inga sidvisningar registrerade ännu.</p>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse; font-size:var(--text-sm);">
            <thead>
                <tr style="border-bottom:2px solid var(--color-border); text-align:left;">
                    <th style="padding:var(--space-2) var(--space-3) var(--space-2) 0; color:var(--color-text-muted); font-weight:var(--weight-medium);">#</th>
                    <th style="padding:var(--space-2) var(--space-3); color:var(--color-text-muted); font-weight:var(--weight-medium);">Plats</th>
                    <th style="padding:var(--space-2) 0 var(--space-2) var(--space-3); color:var(--color-text-muted); font-weight:var(--weight-medium); text-align:right;">Visningar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topPlaces as $i => $row): ?>
                <tr style="border-bottom:1px solid var(--color-border);">
                    <td style="padding:var(--space-2) var(--space-3) var(--space-2) 0; color:var(--color-text-muted);"><?= $i + 1 ?></td>
                    <td style="padding:var(--space-2) var(--space-3);">
                        <a href="/platser/<?= htmlspecialchars($row['slug']) ?>" target="_blank" rel="noopener noreferrer" style="color:inherit;"><?= htmlspecialchars($row['name']) ?></a>
                    </td>
                    <td style="padding:var(--space-2) 0 var(--space-2) var(--space-3); text-align:right; font-weight:var(--weight-semibold);"><?= number_format((int)$row['view_count']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

</div>
