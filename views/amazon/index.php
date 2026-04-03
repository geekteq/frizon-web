<div class="page-header mb-4">
    <h2>Amazon-lista</h2>
    <a href="/adm/amazon-lista/ny" class="btn btn-primary btn--sm">+ Ny produkt</a>
</div>

<?php if (empty($products)): ?>
    <p class="text-muted" style="padding:var(--space-6) 0; font-style:italic;">Inga produkter än — lägg till din första!</p>
<?php else: ?>
<div style="overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; font-size:var(--text-sm);">
        <thead>
            <tr style="border-bottom:2px solid var(--color-border); text-align:left;">
                <th style="padding:var(--space-2) var(--space-3);">Bild</th>
                <th style="padding:var(--space-2) var(--space-3);">Titel</th>
                <th style="padding:var(--space-2) var(--space-3);">Kategori</th>
                <th style="padding:var(--space-2) var(--space-3);">Ordning</th>
                <th style="padding:var(--space-2) var(--space-3);">Featured</th>
                <th style="padding:var(--space-2) var(--space-3);">Status</th>
                <th style="padding:var(--space-2) var(--space-3);"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:var(--space-2) var(--space-3);">
                    <?php if ($p['image_path']): ?>
                        <img src="/uploads/amazon/<?= htmlspecialchars($p['image_path']) ?>"
                             alt="" style="width:48px; height:48px; object-fit:cover; border-radius:var(--radius-sm);">
                    <?php else: ?>
                        <div style="width:48px; height:48px; background:var(--color-bg-muted); border-radius:var(--radius-sm);"></div>
                    <?php endif; ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3); font-weight:var(--weight-medium);">
                    <?= htmlspecialchars($p['title']) ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3); color:var(--color-text-muted);">
                    <?= htmlspecialchars($p['category'] ?? '—') ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3);">
                    <?= (int) $p['sort_order'] ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3);">
                    <?= $p['is_featured'] ? '★' : '—' ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3);">
                    <form method="POST" action="/adm/amazon-lista/<?= (int) $p['id'] ?>/publicera" style="display:inline;">
                        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                        <button type="submit" class="btn btn--sm <?= $p['is_published'] ? 'btn-ghost' : 'btn-secondary' ?>">
                            <?= $p['is_published'] ? 'Avpublicera' : 'Publicera' ?>
                        </button>
                    </form>
                </td>
                <td style="padding:var(--space-2) var(--space-3); white-space:nowrap;">
                    <a href="/adm/amazon-lista/<?= (int) $p['id'] ?>/redigera" class="btn btn-ghost btn--sm">Redigera</a>
                    <?php if ($p['is_published']): ?>
                    <a href="/shop/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="btn btn-ghost btn--sm">Visa</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
