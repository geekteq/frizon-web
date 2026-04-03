<div class="page-header mb-4">
    <a href="/adm/amazon-lista" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Ny produkt</h2>
</div>

<form method="POST" action="/adm/amazon-lista" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="title" class="form-label">Titel *</label>
        <input type="text" id="title" name="title" class="form-input" required
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="amazon_url" class="form-label">Amazon-URL *</label>
        <input type="url" id="amazon_url" name="amazon_url" class="form-input" required
               placeholder="https://www.amazon.se/dp/..."
               value="<?= htmlspecialchars($_POST['amazon_url'] ?? '') ?>">
        <p class="form-hint">Bild och beskrivning hämtas automatiskt vid sparande. Affiliatelänk genereras automatiskt.</p>
    </div>

    <div class="form-group">
        <label for="category" class="form-label">Kategori</label>
        <input type="text" id="category" name="category" class="form-input"
               placeholder="t.ex. Kök, Elektronik, Säkerhet"
               value="<?= htmlspecialchars($_POST['category'] ?? '') ?>"
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
            <button type="button" id="ai-desc-btn" class="btn btn-secondary btn--sm" style="font-size:var(--text-xs);" disabled>Brodera ut text</button>
        </div>
        <textarea id="our_description" name="our_description" class="form-textarea" rows="5"
                  placeholder="Skriv varför ni gillar produkten och hur ni använder den..."><?= htmlspecialchars($_POST['our_description'] ?? '') ?></textarea>
        <p class="form-hint">Spara produkten först för att aktivera AI-broderi (behöver produkt-ID).</p>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
        <div class="form-group">
            <label for="sort_order" class="form-label">Sorteringsordning</label>
            <input type="number" id="sort_order" name="sort_order" class="form-input"
                   value="<?= (int) ($_POST['sort_order'] ?? 0) ?>" min="0">
        </div>
    </div>

    <div style="display:flex; gap:var(--space-4); margin-bottom:var(--space-4);">
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_featured" value="1" <?= !empty($_POST['is_featured']) ? 'checked' : '' ?>>
            <span>Featured</span>
        </label>
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_published" value="1" <?= !empty($_POST['is_published']) ? 'checked' : '' ?>>
            <span>Publicerad</span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary">Spara och hämta info från Amazon</button>
</form>
