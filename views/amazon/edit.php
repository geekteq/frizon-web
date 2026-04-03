<div class="page-header mb-4">
    <a href="/adm/amazon-lista" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera: <?= htmlspecialchars($product['title']) ?></h2>
</div>

<!-- Re-fetch form — must be OUTSIDE the main edit form (no nested forms in HTML) -->
<form method="POST" action="/adm/amazon-lista/<?= (int) $product['id'] ?>/hamta"
      style="max-width:var(--form-max-width); margin-bottom:var(--space-4);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <div style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3) var(--space-4); background:var(--color-bg-muted,#f5f5f4); border-radius:var(--radius-md); border:1px solid var(--color-border);">
        <span style="font-size:var(--text-sm); color:var(--color-text-muted);">Försök hämta bild &amp; beskrivning från Amazon:</span>
        <button type="submit" class="btn btn-secondary btn--sm">Hämta från Amazon</button>
    </div>
</form>

<!-- Main edit form -->
<form method="POST" action="/adm/amazon-lista/<?= (int) $product['id'] ?>"
      enctype="multipart/form-data"
      style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <!-- Bild -->
    <div class="form-group">
        <label class="form-label">Produktbild</label>

        <?php if ($product['image_path']): ?>
            <img src="/uploads/amazon/<?= htmlspecialchars($product['image_path']) ?>"
                 alt="<?= htmlspecialchars($product['title']) ?>"
                 style="display:block; max-width:180px; max-height:180px; object-fit:contain; border-radius:var(--radius-md); border:1px solid var(--color-border); margin-bottom:var(--space-3);">
        <?php else: ?>
            <p style="font-size:var(--text-sm); color:var(--color-warning,#b45309); margin-bottom:var(--space-3);">
                Ingen bild ännu — ladda upp nedan eller klistra in URL.
            </p>
        <?php endif; ?>

        <div style="margin-bottom:var(--space-3);">
            <label class="form-label" style="font-size:var(--text-sm);">Ladda upp bild (jpg/png/webp)</label>
            <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp,image/gif">
        </div>

        <div>
            <label class="form-label" style="font-size:var(--text-sm);">Eller klistra in bild-URL (laddas ner och sparas lokalt)</label>
            <input type="url" name="image_url_manual" class="form-input"
                   placeholder="https://m.media-amazon.com/images/...">
        </div>
    </div>

    <div class="form-group">
        <label for="title" class="form-label">Titel *</label>
        <input type="text" id="title" name="title" class="form-input" required
               value="<?= htmlspecialchars($product['title']) ?>">
    </div>

    <div class="form-group">
        <label for="amazon_url" class="form-label">Amazon-URL *</label>
        <input type="url" id="amazon_url" name="amazon_url" class="form-input" required
               value="<?= htmlspecialchars($product['amazon_url']) ?>">
        <p class="form-hint">Om URL:en ändras hämtas ny bild och beskrivning automatiskt.</p>
    </div>

    <div class="form-group">
        <label class="form-label">Affiliatelänk (auto-genererad)</label>
        <input type="text" class="form-input" readonly value="<?= htmlspecialchars($product['affiliate_url']) ?>"
               style="color:var(--color-text-muted); font-size:var(--text-sm);">
    </div>

    <?php if ($product['amazon_description']): ?>
    <div class="form-group">
        <label class="form-label">Amazon-beskrivning (hämtad)</label>
        <textarea class="form-textarea" rows="3" readonly
                  style="color:var(--color-text-muted); font-size:var(--text-sm);"><?= htmlspecialchars($product['amazon_description']) ?></textarea>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="category" class="form-label">Kategori</label>
        <input type="text" id="category" name="category" class="form-input"
               value="<?= htmlspecialchars($product['category'] ?? '') ?>"
               list="category-suggestions">
        <datalist id="category-suggestions">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--space-2);">
            <label for="our_description" class="form-label" style="margin:0;">Vår beskrivning</label>
            <button type="button" id="ai-desc-btn" class="btn btn-secondary btn--sm" style="font-size:var(--text-xs);">Brodera ut text</button>
        </div>
        <textarea id="our_description" name="our_description" class="form-textarea" rows="5"><?= htmlspecialchars($product['our_description'] ?? '') ?></textarea>
        <p id="ai-desc-status" style="font-size:var(--text-sm); color:var(--color-text-muted); margin-top:var(--space-1); display:none;"></p>
    </div>

    <div style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
        <h3 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-2);">SEO</h3>
        <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">Auto-genereras vid sparande om fälten lämnas tomma.</p>

        <div class="form-group">
            <label for="seo_title" class="form-label">
                SEO-titel
                <span id="seo-title-count" style="color:var(--color-text-muted); font-weight:normal;">(<?= mb_strlen($product['seo_title'] ?? '') ?>/60)</span>
            </label>
            <input type="text" id="seo_title" name="seo_title" class="form-input" maxlength="60"
                   value="<?= htmlspecialchars($product['seo_title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="seo_description" class="form-label">
                SEO-beskrivning
                <span id="seo-desc-count" style="color:var(--color-text-muted); font-weight:normal;">(<?= mb_strlen($product['seo_description'] ?? '') ?>/155)</span>
            </label>
            <input type="text" id="seo_description" name="seo_description" class="form-input" maxlength="155"
                   value="<?= htmlspecialchars($product['seo_description'] ?? '') ?>">
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3); margin-top:var(--space-4);">
        <div class="form-group">
            <label for="sort_order" class="form-label">Sorteringsordning</label>
            <input type="number" id="sort_order" name="sort_order" class="form-input"
                   value="<?= (int) $product['sort_order'] ?>" min="0">
        </div>
    </div>

    <div style="display:flex; gap:var(--space-4); margin-bottom:var(--space-4);">
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
            <span>Featured</span>
        </label>
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_published" value="1" <?= $product['is_published'] ? 'checked' : '' ?>>
            <span>Publicerad</span>
        </label>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/adm/amazon-lista" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<!-- Delete form -->
<form method="POST" action="/adm/amazon-lista/<?= (int) $product['id'] ?>"
      style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm"
            data-confirm="Är du säker? Produkten tas bort permanent.">Ta bort produkt</button>
</form>

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function () {
    var aiBtn     = document.getElementById('ai-desc-btn');
    var descField = document.getElementById('our_description');
    var aiStatus  = document.getElementById('ai-desc-status');

    if (aiBtn && descField) {
        aiBtn.addEventListener('click', function () {
            aiBtn.disabled = true;
            aiBtn.textContent = 'Genererar...';
            aiStatus.style.display = 'block';
            aiStatus.textContent = 'Skapar beskrivning med AI...';

            var csrf = document.querySelector('input[name="_csrf"]');
            fetch('/adm/amazon-lista/<?= (int) $product['id'] ?>/ai/generera', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf ? csrf.value : ''
                },
                body: JSON.stringify({ current_text: descField.value })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    descField.value = data.text;
                    aiStatus.textContent = 'Utkast infogat — redigera och spara.';
                } else {
                    aiStatus.textContent = data.error || 'Något gick fel.';
                }
            })
            .catch(function () {
                aiStatus.textContent = 'Nätverksfel — försök igen.';
            })
            .finally(function () {
                aiBtn.disabled = false;
                aiBtn.textContent = 'Brodera ut text';
            });
        });
    }

    var seoTitle = document.getElementById('seo_title');
    var seoTitleCount = document.getElementById('seo-title-count');
    if (seoTitle && seoTitleCount) {
        seoTitle.addEventListener('input', function () {
            seoTitleCount.textContent = '(' + seoTitle.value.length + '/60)';
        });
    }

    var seoDesc = document.getElementById('seo_description');
    var seoDescCount = document.getElementById('seo-desc-count');
    if (seoDesc && seoDescCount) {
        seoDesc.addEventListener('input', function () {
            seoDescCount.textContent = '(' + seoDesc.value.length + '/155)';
        });
    }
});
</script>
