<div class="page-header mb-4">
    <a href="/adm/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera <?= htmlspecialchars($p['name']) ?></h2>
</div>

<!-- Live map preview -->
<div id="edit-map" style="width:100%; height:200px; border-radius:var(--radius-lg); margin-bottom:var(--space-4); background:var(--color-brand-muted);"></div>

<form method="POST" action="/adm/platser/<?= htmlspecialchars($p['slug']) ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="form-group">
        <label for="name" class="form-label">Namn *</label>
        <input type="text" id="name" name="name" class="form-input" required value="<?= htmlspecialchars($p['name']) ?>">
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
                    <input type="radio" name="place_type" value="<?= $val ?>" <?= $p['place_type'] === $val ? 'checked' : '' ?>>
                    <span class="chip chip--<?= $val ?>"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
        <div class="form-group">
            <label for="lat" class="form-label">Latitud</label>
            <input type="text" id="lat" name="lat" class="form-input" inputmode="decimal" value="<?= htmlspecialchars((string) $p['lat']) ?>">
        </div>
        <div class="form-group">
            <label for="lng" class="form-label">Longitud</label>
            <input type="text" id="lng" name="lng" class="form-input" inputmode="decimal" value="<?= htmlspecialchars((string) $p['lng']) ?>">
        </div>
    </div>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">Klicka på kartan eller dra markören för att flytta positionen.</p>

    <div class="form-group">
        <label for="address_text" class="form-label">Adress</label>
        <input type="text" id="address_text" name="address_text" class="form-input" value="<?= htmlspecialchars($p['address_text'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="country_code" class="form-label">Land</label>
        <select id="country_code" name="country_code" class="form-select">
            <option value="">Välj land</option>
            <?php
            $countries = ['SE'=>'Sverige','NO'=>'Norge','DK'=>'Danmark','FI'=>'Finland','DE'=>'Tyskland','FR'=>'Frankrike','IT'=>'Italien','ES'=>'Spanien','PT'=>'Portugal','NL'=>'Nederländerna','BE'=>'Belgien','AT'=>'Österrike','CH'=>'Schweiz','PL'=>'Polen','CZ'=>'Tjeckien','HR'=>'Kroatien','GR'=>'Grekland'];
            foreach ($countries as $code => $name): ?>
                <option value="<?= $code ?>" <?= ($p['country_code'] ?? '') === $code ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--space-2);">
            <label for="default_public_text" class="form-label" style="margin:0;">Beskrivning (visas publikt)</label>
            <button type="button" id="ai-place-btn" class="btn btn-secondary btn--sm" style="font-size:var(--text-xs);">Brodera ut text</button>
        </div>
        <textarea id="default_public_text" name="default_public_text" class="form-textarea" rows="4"><?= htmlspecialchars($p['default_public_text'] ?? '') ?></textarea>
        <p id="ai-place-status" style="font-size:var(--text-sm); color:var(--color-text-muted); margin-top:var(--space-1); display:none;"></p>
    </div>

<!-- SEO section -->
<div style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
    <h3 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-2);">SEO-innehåll</h3>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">Genereras automatiskt vid publicering. Kan redigeras fritt efteråt.</p>

    <div class="form-group">
        <label for="meta_description" class="form-label">
            Meta-beskrivning
            <span id="meta-count" style="color:var(--color-text-muted); font-weight:normal;">(<?= mb_strlen($p['meta_description'] ?? '') ?>/155)</span>
        </label>
        <input type="text" id="meta_description" name="meta_description" class="form-input"
               maxlength="155" value="<?= htmlspecialchars($p['meta_description'] ?? '') ?>">
        <p style="font-size:var(--text-xs); color:var(--color-text-muted); margin-top:var(--space-1);">Visas i sökmotorer. Rekommenderas max 155 tecken.</p>
    </div>

    <div class="form-group">
        <label class="form-label">FAQ-block</label>
        <div id="faq-rows">
            <?php
            $faqEditItems = !empty($p['faq_content']) ? (json_decode($p['faq_content'], true) ?? []) : [];
            foreach ($faqEditItems as $faqItem):
            ?>
                <div class="faq-row" style="display:grid; gap:var(--space-2); margin-bottom:var(--space-3); padding:var(--space-3); background:var(--color-bg-muted,var(--color-bg)); border:1px solid var(--color-border); border-radius:var(--radius-md);">
                    <input type="text" name="faq_q[]" class="form-input" placeholder="Fråga" value="<?= htmlspecialchars($faqItem['q'] ?? '') ?>">
                    <textarea name="faq_a[]" class="form-textarea" rows="2" placeholder="Svar"><?= htmlspecialchars($faqItem['a'] ?? '') ?></textarea>
                    <button type="button" class="btn btn-ghost btn--sm faq-remove" style="justify-self:start;">Ta bort</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="faq-add" class="btn btn-ghost btn--sm" style="margin-top:var(--space-2);">+ Lägg till fråga</button>
    </div>
</div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/adm/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/adm/platser/<?= htmlspecialchars($p['slug']) ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" data-confirm="Är du säker? Alla besök tas också bort.">Ta bort plats</button>
</form>

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var latInput = document.getElementById('lat');
    var lngInput = document.getElementById('lng');
    var mapEl = document.getElementById('edit-map');
    if (!mapEl || !latInput || !lngInput) return;

    var lat = parseFloat(latInput.value) || 59.33;
    var lng = parseFloat(lngInput.value) || 18.07;

    var map = L.map(mapEl).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 19
    }).addTo(map);

    var marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    function syncInputs(pos) {
        latInput.value = pos.lat.toFixed(7);
        lngInput.value = pos.lng.toFixed(7);
    }

    function syncMap() {
        var newLat = parseFloat(latInput.value);
        var newLng = parseFloat(lngInput.value);
        if (!isNaN(newLat) && !isNaN(newLng) && newLat !== 0 && newLng !== 0) {
            var pos = L.latLng(newLat, newLng);
            marker.setLatLng(pos);
            map.setView(pos, map.getZoom());
        }
    }

    marker.on('dragend', function() { syncInputs(marker.getLatLng()); });
    map.on('click', function(e) { marker.setLatLng(e.latlng); syncInputs(e.latlng); });
    latInput.addEventListener('change', syncMap);
    lngInput.addEventListener('change', syncMap);

    // AI generate for place description
    var aiBtn = document.getElementById('ai-place-btn');
    var aiStatus = document.getElementById('ai-place-status');
    var descField = document.getElementById('default_public_text');
    if (aiBtn && descField) {
        aiBtn.addEventListener('click', function() {
            aiBtn.disabled = true;
            aiBtn.textContent = 'Genererar...';
            aiStatus.style.display = 'block';
            aiStatus.textContent = 'Skapar utkast med AI...';

            var csrf = document.querySelector('input[name="_csrf"]');
            fetch('/adm/platser/<?= htmlspecialchars($p['slug']) ?>/ai/generera', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf ? csrf.value : ''
                },
                body: JSON.stringify({ current_text: descField.value })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    descField.value = data.text;
                    aiStatus.textContent = 'Utkast infogat — redigera och spara.';
                } else {
                    aiStatus.textContent = data.error || 'Något gick fel.';
                }
            })
            .catch(function() {
                aiStatus.textContent = 'Nätverksfel — försök igen.';
            })
            .finally(function() {
                aiBtn.disabled = false;
                aiBtn.textContent = 'Brodera ut text';
            });
        });
    }

    // Meta description char counter
    var metaInput = document.getElementById('meta_description');
    var metaCount = document.getElementById('meta-count');
    if (metaInput && metaCount) {
        metaInput.addEventListener('input', function() {
            metaCount.textContent = '(' + metaInput.value.length + '/155)';
        });
    }

    // FAQ add/remove rows
    var faqRows = document.getElementById('faq-rows');
    var faqAdd  = document.getElementById('faq-add');

    function makeFaqRow() {
        var row = document.createElement('div');
        row.className = 'faq-row';
        row.style.cssText = 'display:grid; gap:var(--space-2); margin-bottom:var(--space-3); padding:var(--space-3); background:var(--color-bg-muted,var(--color-bg)); border:1px solid var(--color-border); border-radius:var(--radius-md);';
        row.innerHTML = '<input type="text" name="faq_q[]" class="form-input" placeholder="Fråga" value="">'
            + '<textarea name="faq_a[]" class="form-textarea" rows="2" placeholder="Svar"></textarea>'
            + '<button type="button" class="btn btn-ghost btn--sm faq-remove" style="justify-self:start;">Ta bort</button>';
        return row;
    }

    if (faqAdd && faqRows) {
        faqAdd.addEventListener('click', function() {
            faqRows.appendChild(makeFaqRow('', ''));
        });

        faqRows.addEventListener('click', function(e) {
            if (e.target.classList.contains('faq-remove')) {
                e.target.closest('.faq-row').remove();
            }
        });
    }
});
</script>
