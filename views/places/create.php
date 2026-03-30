<div class="page-header mb-4">
    <a href="/platser" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Ny plats</h2>
</div>

<div id="gps-map" style="width:100%; height:180px; border-radius:var(--radius-lg); margin-bottom:var(--space-4); background:var(--color-brand-muted);"></div>

<form method="POST" action="/platser" class="place-form" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <input type="hidden" name="lat" id="place-lat" value="">
    <input type="hidden" name="lng" id="place-lng" value="">

    <div class="form-group">
        <label for="name" class="form-label">Namn *</label>
        <input type="text" id="name" name="name" class="form-input" required placeholder="Platsnamn...">
    </div>

    <div class="form-group">
        <label class="form-label">Typ</label>
        <div class="chip-row" style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
            <?php
            $types = [
                'stellplatz'=>'Ställplats','camping'=>'Camping','wild_camping'=>'Vildcamping',
                'fika'=>'Fika','lunch'=>'Lunch','dinner'=>'Middag','breakfast'=>'Frukost',
                'sight'=>'Sevärdhet','shopping'=>'Shopping'
            ];
            foreach ($types as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="place_type" value="<?= $val ?>" <?= $val === 'stellplatz' ? 'checked' : '' ?>>
                    <span class="chip chip--<?= $val ?>"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="address_text" class="form-label">Adress (valfritt)</label>
        <input type="text" id="address_text" name="address_text" class="form-input" placeholder="Gatuadress, stad...">
    </div>

    <div class="form-group">
        <label for="country_code" class="form-label">Land</label>
        <select id="country_code" name="country_code" class="form-select">
            <option value="">Välj land</option>
            <option value="SE" selected>Sverige</option>
            <option value="NO">Norge</option>
            <option value="DK">Danmark</option>
            <option value="FI">Finland</option>
            <option value="DE">Tyskland</option>
            <option value="FR">Frankrike</option>
            <option value="IT">Italien</option>
            <option value="ES">Spanien</option>
            <option value="PT">Portugal</option>
            <option value="NL">Nederländerna</option>
            <option value="BE">Belgien</option>
            <option value="AT">Österrike</option>
            <option value="CH">Schweiz</option>
            <option value="PL">Polen</option>
            <option value="CZ">Tjeckien</option>
            <option value="HR">Kroatien</option>
            <option value="GR">Grekland</option>
        </select>
    </div>

    <div class="form-group">
        <label for="raw_note" class="form-label">Kort anteckning (valfritt)</label>
        <textarea id="raw_note" name="raw_note" class="form-textarea form-textarea--note" rows="3" placeholder="Valfri anteckning..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn--full">Spara plats</button>
</form>

<script src="/js/gps.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initGpsCapture('gps-map', 'place-lat', 'place-lng');
});
</script>
