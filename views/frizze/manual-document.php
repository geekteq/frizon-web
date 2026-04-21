<div class="frizze frizze-markdown">
    <section class="frizze-hero">
        <div>
            <p class="frizze-eyebrow">Internt fordonsnav</p>
            <h1><?= htmlspecialchars($document['title']) ?></h1>
            <p class="frizze-hero__meta"><?= htmlspecialchars($document['filename']) ?></p>
        </div>
    </section>

    <nav class="frizze-tabs" aria-label="Frizze-sektioner">
        <?php foreach ($tabs as $key => $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="frizze-tabs__item <?= $key === 'manual' ? 'frizze-tabs__item--active' : '' ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="frizze-markdown__toolbar">
        <a href="/adm/frizze/manual" class="btn btn-ghost btn--sm">&larr; Dokumentation</a>
    </div>

    <article class="frizze-markdown__body">
        <?= $documentHtml ?>
    </article>
</div>
