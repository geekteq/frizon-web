<div class="page-header mb-4">
    <a href="/adm/platser" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Ny plats</h2>
</div>

<!-- Mode toggle -->
<div style="display:flex; gap:var(--space-2); margin-bottom:var(--space-3);">
    <button type="button" id="mode-gps" class="chip chip--active" style="cursor:pointer; border:none; padding:var(--space-2) var(--space-4);">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
        Hämta GPS-position
    </button>
    <button type="button" id="mode-manual" class="chip" style="cursor:pointer; border:none; padding:var(--space-2) var(--space-4); opacity:0.6;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Ange manuellt
    </button>
</div>

<!-- GPS/map status message -->
<p id="gps-status" style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-2); min-height:1.4em;"></p>

<!-- Map — always visible, click to set position in both modes -->
<div id="gps-map" style="width:100%; height:220px; border-radius:var(--radius-lg); margin-bottom:var(--space-4); background:var(--color-brand-muted);"></div>

<!-- Manual coordinate fields — hidden by default, shown in manual mode -->
<div id="manual-fields" style="display:none; margin-bottom:var(--space-4);">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
        <div class="form-group" style="margin-bottom:0;">
            <label for="manual-lat" class="form-label">Latitud</label>
            <input type="text" id="manual-lat" class="form-input" placeholder="t.ex. 59.3293" inputmode="decimal" autocomplete="off">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="manual-lng" class="form-label">Longitud</label>
            <input type="text" id="manual-lng" class="form-input" placeholder="t.ex. 18.0686" inputmode="decimal" autocomplete="off">
        </div>
    </div>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-top:var(--space-2);">
        Tips: klistra in koordinater eller klicka direkt på kartan ovan.
    </p>
</div>

<form method="POST" action="/adm/platser" class="place-form" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <!-- Hidden inputs that hold the final lat/lng submitted with the form -->
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
                'stellplatz'=>'Ställplats','camping'=>'Camping','wild_camping'=>'Fricamping',
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
