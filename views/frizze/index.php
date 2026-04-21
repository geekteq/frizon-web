<?php
$tab = $activeTab ?? 'overview';
$statusLabels = [
    'ok' => 'I fas',
    'watch' => 'Bevaka',
    'due' => 'Att göra',
];
?>

<div class="frizze">
    <section class="frizze-hero">
        <div>
            <p class="frizze-eyebrow">Internt fordonsnav</p>
            <h1><?= htmlspecialchars($vehicle['name']) ?></h1>
            <p class="frizze-hero__meta">
                <?= htmlspecialchars($vehicle['model']) ?> · <?= htmlspecialchars($vehicle['base']) ?> · <?= htmlspecialchars($vehicle['registration']) ?>
            </p>
        </div>
        <div class="frizze-budget">
            <span>Budget <?= htmlspecialchars($budget['year']) ?></span>
            <strong><?= htmlspecialchars($budget['planned']) ?></strong>
            <small>Buffert: <?= htmlspecialchars($budget['buffer']) ?></small>
        </div>
    </section>

    <nav class="frizze-tabs" aria-label="Frizze-sektioner">
        <?php foreach ($tabs as $key => $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="frizze-tabs__item <?= $tab === $key ? 'frizze-tabs__item--active' : '' ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($tab === 'overview'): ?>
        <section class="frizze-grid frizze-grid--status">
            <?php foreach ($statusCards as $card): ?>
                <article class="frizze-status frizze-status--<?= htmlspecialchars($card['state']) ?>">
                    <div class="frizze-status__top">
                        <span><?= htmlspecialchars($card['label']) ?></span>
                        <em><?= htmlspecialchars($statusLabels[$card['state']] ?? $card['state']) ?></em>
                    </div>
                    <strong><?= htmlspecialchars($card['value']) ?></strong>
                    <p><?= htmlspecialchars($card['meta']) ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="frizze-split">
            <article class="frizze-panel">
                <div class="frizze-panel__header">
                    <h2>Fordonsdata</h2>
                    <a href="/adm/frizze/utrustning">Utrustning</a>
                </div>
                <dl class="frizze-facts">
                    <div><dt>Modell</dt><dd><?= htmlspecialchars($vehicle['model']) ?></dd></div>
                    <div><dt>Motor</dt><dd><?= htmlspecialchars($vehicle['engine']) ?></dd></div>
                    <div><dt>Reg.nr</dt><dd><?= htmlspecialchars($vehicle['registration']) ?></dd></div>
                    <div><dt>Chassi</dt><dd><?= htmlspecialchars($vehicle['vin']) ?></dd></div>
                    <div><dt>Mätarställning</dt><dd><?= htmlspecialchars($vehicle['odometer']) ?></dd></div>
                    <div><dt>Köpt</dt><dd><?= htmlspecialchars($vehicle['purchased_at']) ?></dd></div>
                </dl>
            </article>

            <article class="frizze-panel">
                <div class="frizze-panel__header">
                    <h2>2027-plan</h2>
                    <a href="/adm/frizze/serviceplan">Serviceplan</a>
                </div>
                <div class="frizze-budget-lines">
                    <?php foreach ($budget['lines'] as $line): ?>
                        <div>
                            <span><?= htmlspecialchars($line['label']) ?></span>
                            <strong><?= htmlspecialchars($line['amount']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="frizze-panel">
            <div class="frizze-panel__header">
                <h2>Senaste händelser</h2>
                <a href="/adm/frizze/journal">Visa journal</a>
            </div>
            <div class="frizze-mini-timeline">
                <?php foreach (array_slice($journal, 0, 3) as $item): ?>
                    <div>
                        <time><?= htmlspecialchars($item['date']) ?></time>
                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                        <span><?= htmlspecialchars($item['meta']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="frizze-actions">
            <a href="/adm/frizze/journal" class="frizze-action">
                <strong>Lägg till händelse</strong>
                <span>Service, reparation, besiktning eller kontroll.</span>
            </a>
            <a href="/adm/frizze/kvitton" class="frizze-action">
                <strong>Ladda upp kvitto</strong>
                <span>Förbered ytan för Anthropic-tolkning och granskning.</span>
            </a>
            <a href="/adm/frizze/manual" class="frizze-action">
                <strong>Sök i manualen</strong>
                <span>El, gasol, vinter, säkerhet och kända problem.</span>
            </a>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'journal'): ?>
        <section class="frizze-panel">
            <div class="frizze-panel__header">
                <h2>Fordonsjournal</h2>
                <button class="btn btn-ghost btn--sm" type="button" disabled>+ Ny händelse</button>
            </div>
            <div class="frizze-timeline">
                <?php foreach ($journal as $item): ?>
                    <article class="frizze-timeline__item">
                        <time><?= htmlspecialchars($item['date']) ?></time>
                        <div>
                            <span class="frizze-chip"><?= htmlspecialchars($item['type']) ?></span>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p><?= htmlspecialchars($item['meta']) ?></p>
                            <ul>
                                <?php foreach ($item['details'] as $detail): ?>
                                    <li><?= htmlspecialchars($detail) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'receipts'): ?>
        <section class="frizze-panel">
            <div class="frizze-panel__header">
                <h2>Kvitton och tolkning</h2>
                <button class="btn btn-primary btn--sm" type="button" disabled>Ladda upp</button>
            </div>
            <div class="frizze-receipt-flow">
                <div><strong>1. Fota eller ladda upp</strong><span>PDF, JPG eller PNG från mobil.</span></div>
                <div><strong>2. Tolka med Anthropic</strong><span>Datum, leverantör, totalsumma, rader och åtgärder.</span></div>
                <div><strong>3. Granska och redigera</strong><span>Du godkänner innan journalen uppdateras.</span></div>
            </div>
            <p class="frizze-note">Nästa tekniska steg är DB-tabeller för dokument, tolkningsförslag och journalhändelser. Den här sidan är därför avsiktligt skrivskyddad i första versionen.</p>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'service'): ?>
        <section class="frizze-panel">
            <div class="frizze-panel__header">
                <h2>Serviceplan</h2>
                <span>2026-2035</span>
            </div>
            <div class="frizze-service-list">
                <?php foreach ($servicePlan as $item): ?>
                    <article>
                        <div class="frizze-service-list__year">
                            <strong><?= htmlspecialchars($item['year']) ?></strong>
                            <span><?= htmlspecialchars($item['km']) ?></span>
                        </div>
                        <div>
                            <p><?= htmlspecialchars($item['summary']) ?></p>
                            <div class="frizze-tags">
                                <?php foreach ($item['items'] as $serviceItem): ?>
                                    <span><?= htmlspecialchars($serviceItem) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'equipment'): ?>
        <section class="frizze-grid frizze-grid--equipment">
            <?php foreach ($equipment as $group => $items): ?>
                <article class="frizze-panel">
                    <h2><?= htmlspecialchars($group) ?></h2>
                    <ul class="frizze-clean-list">
                        <?php foreach ($items as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'manual'): ?>
        <section class="frizze-grid frizze-grid--manual">
            <?php foreach ($manualSections as $section): ?>
                <article class="frizze-panel">
                    <h2><?= htmlspecialchars($section['title']) ?></h2>
                    <dl class="frizze-facts frizze-facts--stacked">
                        <?php foreach ($section['items'] as $item): ?>
                            <div>
                                <dt><?= htmlspecialchars($item['label']) ?></dt>
                                <dd><?= htmlspecialchars($item['value']) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>
