<?php
/**
 * Reusable product picker (checkbox list).
 *
 * Required vars in scope:
 *   - $allProducts        array  list of amazon_product rows
 *   - $attachedProductIds int[]  currently attached product IDs
 *   - $pickerTitle        string heading text
 *   - $pickerDescription  string short helper text under the heading
 *
 * Posts as `product_ids[]` checkboxes — controllers handle persistence.
 */
if (empty($allProducts)) return;
?>
<section style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <h2 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">
        <?= htmlspecialchars($pickerTitle) ?>
    </h2>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">
        <?= htmlspecialchars($pickerDescription) ?>
    </p>
    <div style="display:flex; flex-direction:column; gap:var(--space-2);">
        <?php foreach ($allProducts as $prod): ?>
            <label style="display:flex; align-items:center; gap:var(--space-3); cursor:pointer; padding:var(--space-2) 0;">
                <input type="checkbox"
                       name="product_ids[]"
                       value="<?= (int) $prod['id'] ?>"
                       <?= in_array((int) $prod['id'], $attachedProductIds ?? [], true) ? 'checked' : '' ?>>
                <?php if ($prod['image_path']): ?>
                    <img src="/uploads/amazon/<?= htmlspecialchars($prod['image_path']) ?>"
                         alt="" width="40" height="40"
                         style="width:40px; height:40px; object-fit:contain; background:#f5f5f4; border-radius:var(--radius-sm); flex-shrink:0;">
                <?php endif; ?>
                <span style="font-size:var(--text-sm);"><?= htmlspecialchars($prod['title']) ?></span>
                <?php if ($prod['category']): ?>
                    <span style="font-size:var(--text-xs); color:var(--color-text-muted); margin-left:auto;">
                        <?= htmlspecialchars($prod['category']) ?>
                    </span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
</section>
