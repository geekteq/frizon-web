<div class="page-header mb-4">
    <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera <?= htmlspecialchars($p['name']) ?></h2>
</div>

<form method="POST" action="/platser/<?= htmlspecialchars($p['slug']) ?>" style="max-width:var(--form-max-width);">
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
                'stellplatz'=>'Ställplats','camping'=>'Camping','wild_camping'=>'Vildcamping',
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

    <div class="form-group">
        <label for="lat" class="form-label">Latitud</label>
        <input type="number" id="lat" name="lat" class="form-input" step="0.0000001" value="<?= htmlspecialchars((string) $p['lat']) ?>">
    </div>

    <div class="form-group">
        <label for="lng" class="form-label">Longitud</label>
        <input type="number" id="lng" name="lng" class="form-input" step="0.0000001" value="<?= htmlspecialchars((string) $p['lng']) ?>">
    </div>

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

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/platser/<?= htmlspecialchars($p['slug']) ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Är du säker? Alla besök tas också bort.')">Ta bort plats</button>
</form>
