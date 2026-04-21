<div class="page-header mb-4">
    <a href="/adm/frizze/kvitton" class="btn-ghost btn--sm">&larr; Dokumentarkiv</a>
    <h2><?= htmlspecialchars($pageTitle) ?></h2>
</div>

<section class="frizze-panel frizze-ai-note">
    <div>
        <span class="frizze-chip"><?= htmlspecialchars($interpretation['status']) ?></span>
        <?php if (!empty($draft['confidence'])): ?>
            <span class="frizze-document-list__status">Säkerhet: <?= htmlspecialchars($draft['confidence']) ?></span>
        <?php endif; ?>
    </div>
    <h3><?= htmlspecialchars($interpretation['document_title']) ?></h3>
    <p>
        Granska tolkningen innan den sparas som journalhändelse. Originalfilen är fortfarande privat och kan bara öppnas via admin.
    </p>
    <a class="btn btn-ghost btn--sm" href="/adm/frizze/dokument/<?= (int) $interpretation['document_id'] ?>" target="_blank" rel="noopener">Öppna original</a>
</section>

<form method="POST" action="/adm/frizze/tolkningar/<?= (int) $interpretation['id'] ?>/spara" class="frizze-event-form frizze-interpretation-form">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="confidence" value="<?= htmlspecialchars($draft['confidence']) ?>">

    <div class="form-group">
        <label for="title" class="form-label">Titel *</label>
        <input type="text" id="title" name="title" class="form-input" required value="<?= htmlspecialchars($draft['title']) ?>">
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="document_type" class="form-label">Dokumenttyp</label>
            <select id="document_type" name="document_type" class="form-select">
                <?php foreach ($documentTypes as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= $draft['document_type'] === $value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="document_date" class="form-label">Dokumentdatum</label>
            <input type="date" id="document_date" name="document_date" class="form-input" value="<?= htmlspecialchars($draft['document_date']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Journaltyp</label>
        <div class="flex gap-2 flex-wrap">
            <?php foreach ($eventTypes as $value => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="event_type" value="<?= htmlspecialchars($value) ?>" <?= $draft['event_type'] === $value ? 'checked' : '' ?>>
                    <span class="chip"><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="event_date" class="form-label">Händelsedatum *</label>
            <input type="date" id="event_date" name="event_date" class="form-input" required value="<?= htmlspecialchars($draft['event_date']) ?>">
        </div>

        <div class="form-group">
            <label for="event_time" class="form-label">Tid</label>
            <input type="time" id="event_time" name="event_time" class="form-input" value="<?= htmlspecialchars($draft['event_time']) ?>">
        </div>
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="supplier" class="form-label">Leverantör / verkstad</label>
            <input type="text" id="supplier" name="supplier" class="form-input" value="<?= htmlspecialchars($draft['supplier']) ?>">
        </div>

        <div class="form-group">
            <label for="odometer_km" class="form-label">Mätarställning, km</label>
            <input type="number" id="odometer_km" name="odometer_km" class="form-input" min="0" step="1" value="<?= htmlspecialchars($draft['odometer_km']) ?>">
        </div>
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="amount_total" class="form-label">Belopp</label>
            <input type="text" id="amount_total" name="amount_total" class="form-input" inputmode="decimal" value="<?= htmlspecialchars($draft['amount_total']) ?>">
        </div>

        <div class="form-group">
            <label for="currency" class="form-label">Valuta</label>
            <input type="text" id="currency" name="currency" class="form-input" value="<?= htmlspecialchars($draft['currency']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Kort notering</label>
        <textarea id="description" name="description" class="form-textarea" rows="3"><?= htmlspecialchars($draft['description']) ?></textarea>
    </div>

    <div class="form-group">
        <label for="details" class="form-label">Utfört / detaljer</label>
        <textarea id="details" name="details" class="form-textarea" rows="7" placeholder="En rad per åtgärd"><?= htmlspecialchars(implode("\n", array_map('strval', $draft['details']))) ?></textarea>
    </div>

    <div class="form-group">
        <label for="needs_review" class="form-label">Att kontrollera manuellt</label>
        <textarea id="needs_review" name="needs_review" class="form-textarea" rows="4" placeholder="En rad per osäker punkt"><?= htmlspecialchars(implode("\n", array_map('strval', $draft['needs_review']))) ?></textarea>
    </div>

    <?php if ($interpretation['status'] === 'applied'): ?>
        <p class="frizze-note">Tolkningen är redan godkänd och har skapats som journalhändelse.</p>
        <a href="/adm/frizze/journal" class="btn btn-primary">Visa journal</a>
    <?php else: ?>
        <div class="flex gap-3 flex-wrap">
            <button type="submit" class="btn btn-secondary">Spara tolkning</button>
            <button type="submit" class="btn btn-primary" formaction="/adm/frizze/tolkningar/<?= (int) $interpretation['id'] ?>/godkann">
                Godkänn och skapa journalhändelse
            </button>
            <a href="/adm/frizze/kvitton" class="btn btn-ghost">Avbryt</a>
        </div>
    <?php endif; ?>
</form>

<?php if ($interpretation['status'] !== 'applied'): ?>
    <form method="POST" action="/adm/frizze/tolkningar/<?= (int) $interpretation['id'] ?>/avvisa" class="frizze-delete-form">
        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
        <button type="submit" class="btn btn-danger btn--sm" data-confirm="Avvisa tolkningen? Dokumentet finns kvar.">Avvisa tolkning</button>
    </form>
<?php endif; ?>
