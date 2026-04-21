<div class="page-header mb-4">
    <a href="/adm/frizze/journal" class="btn-ghost btn--sm">&larr; Journal</a>
    <h2><?= htmlspecialchars($pageTitle) ?></h2>
</div>

<form method="POST" action="<?= htmlspecialchars($formAction) ?>" class="frizze-event-form">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <?php if ($formMethod === 'PUT'): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <div class="form-group">
        <label for="title" class="form-label">Titel *</label>
        <input
            type="text"
            id="title"
            name="title"
            class="form-input"
            required
            value="<?= htmlspecialchars($event['title'] ?? '') ?>"
            placeholder="t.ex. Oljebyte och gasoltest"
        >
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="event_date" class="form-label">Datum *</label>
            <input type="date" id="event_date" name="event_date" class="form-input" required value="<?= htmlspecialchars($event['event_date'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="event_time" class="form-label">Tid</label>
            <input type="time" id="event_time" name="event_time" class="form-input" value="<?= htmlspecialchars(substr((string) ($event['event_time'] ?? ''), 0, 5)) ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Typ</label>
        <div class="flex gap-2 flex-wrap">
            <?php foreach ($eventTypes as $value => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="event_type" value="<?= htmlspecialchars($value) ?>" <?= ($event['event_type'] ?? 'service') === $value ? 'checked' : '' ?>>
                    <span class="chip"><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="supplier" class="form-label">Leverantör / verkstad</label>
            <input type="text" id="supplier" name="supplier" class="form-input" value="<?= htmlspecialchars($event['supplier'] ?? '') ?>" placeholder="t.ex. Torvalla LCV">
        </div>

        <div class="form-group">
            <label for="odometer_km" class="form-label">Mätarställning, km</label>
            <input type="number" id="odometer_km" name="odometer_km" class="form-input" min="0" step="1" value="<?= htmlspecialchars((string) ($event['odometer_km'] ?? '')) ?>" placeholder="100000">
        </div>
    </div>

    <div class="frizze-form-grid">
        <div class="form-group">
            <label for="amount_total" class="form-label">Kostnad, kr</label>
            <input type="text" id="amount_total" name="amount_total" class="form-input" inputmode="decimal" value="<?= htmlspecialchars(isset($event['amount_total']) ? (string) $event['amount_total'] : '') ?>" placeholder="3743">
        </div>

        <div class="form-group">
            <label for="document_id" class="form-label">Kopplat dokument</label>
            <select id="document_id" name="document_id" class="form-select">
                <option value="">Inget dokument ännu</option>
                <?php foreach ($documents as $document): ?>
                    <option value="<?= (int) $document['id'] ?>" <?= (int) ($event['document_id'] ?? 0) === (int) $document['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($document['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Kort notering</label>
        <textarea id="description" name="description" class="form-textarea" rows="3" placeholder="Kort sammanfattning eller kontext..."><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="details" class="form-label">Utfört / detaljer</label>
        <textarea id="details" name="details" class="form-textarea" rows="6" placeholder="En rad per åtgärd"><?= htmlspecialchars(implode("\n", $event['details'] ?? [])) ?></textarea>
    </div>

    <div class="flex gap-3 flex-wrap">
        <button type="submit" class="btn btn-primary">Spara</button>
        <a href="/adm/frizze/journal" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<?php if (!empty($event['id'])): ?>
    <form method="POST" action="/adm/frizze/journal/<?= (int) $event['id'] ?>" class="frizze-delete-form">
        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="btn btn-danger btn--sm" data-confirm="Ta bort journalhändelsen?">Ta bort händelse</button>
    </form>
<?php endif; ?>
