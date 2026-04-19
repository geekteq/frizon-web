# Visit Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Separera plats från besök i presentationen, gör besökstext redigerbar, lägg till publik besökssida, och fixa AI-prompter till jag/vi-form.

**Architecture:** Bygger vidare på befintlig PHP MVC-struktur. En ny migration (preview_image_id), en ny publik route+vy, utökad edit-vy, och prompt-ändringar i AiService. Inga nya beroenden.

**Tech Stack:** PHP 8.x, MariaDB, vanilla JS, Leaflet, LiteSpeed

---

### Task 1: Migration — preview_image_id på places

**Files:**
- Create: `database/migrations/014_place_preview_image.sql`

- [ ] **Step 1: Skapa migrationsfilen**

```sql
ALTER TABLE places
    ADD COLUMN preview_image_id INT UNSIGNED NULL DEFAULT NULL AFTER toplist_order,
    ADD CONSTRAINT fk_places_preview_image
        FOREIGN KEY (preview_image_id) REFERENCES visit_images(id)
        ON DELETE SET NULL;
```

- [ ] **Step 2: Kör migrationen lokalt**

Run: `/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/014_place_preview_image.sql`
Expected: Query OK

- [ ] **Step 3: Verifiera kolumnen**

Run: `/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "DESCRIBE places preview_image_id"`
Expected: `preview_image_id | int(10) unsigned | YES | | NULL |`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/014_place_preview_image.sql
git commit -m "feat: add preview_image_id to places table"
```

---

### Task 2: Redigerbar besökstext i edit-vyn

**Files:**
- Modify: `views/visits/edit.php` (rad 100-103, före submit-knappen)
- Modify: `app/Controllers/VisitController.php:160-170` (update-metoden)

- [ ] **Step 1: Lägg till approved_public_text-fält i edit-vyn**

I `views/visits/edit.php`, före raden `<button type="submit" class="btn btn-primary btn--full">Spara ändringar</button>` (rad 102), lägg till:

```php
    <div class="form-group" style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
        <label for="approved_public_text" class="form-label">Publicerad text</label>
        <?php if ($visit['ready_for_publish']): ?>
            <span class="text-sm" style="color:var(--color-success); font-weight:600;">Publicerad</span>
        <?php endif; ?>
        <textarea id="approved_public_text" name="approved_public_text" class="form-textarea" rows="6"
            style="border-color:<?= $visit['ready_for_publish'] ? 'var(--color-success)' : 'var(--color-border)' ?>;"
            placeholder="Redigera den publika texten direkt här. Spara utan att köra om AI."
        ><?= htmlspecialchars($visit['approved_public_text'] ?? '') ?></textarea>
        <span class="form-hint">Redigera direkt. Använd "Brodera ut text" på besökssidan för att generera ny AI-text.</span>
    </div>
```

- [ ] **Step 2: Lägg till approved_public_text i VisitController::update()**

I `app/Controllers/VisitController.php`, ändra update-arrayen i `update()` (rad 160-170). Lägg till `approved_public_text` i arrayen som skickas till `$visitModel->update()`:

Byt ut:
```php
        $visitModel->update((int) $params['id'], [
            'visited_at'     => $_POST['visited_at'] ?? $visit['visited_at'],
            'raw_note'       => trim($_POST['raw_note'] ?? '') ?: null,
            'plus_notes'     => trim($_POST['plus_notes'] ?? '') ?: null,
            'minus_notes'    => trim($_POST['minus_notes'] ?? '') ?: null,
            'tips_notes'     => trim($_POST['tips_notes'] ?? '') ?: null,
            'price_level'    => $_POST['price_level'] ?? null,
            'would_return'   => $_POST['would_return'] ?? null,
            'suitable_for'   => trim($_POST['suitable_for'] ?? '') ?: null,
            'things_to_note' => trim($_POST['things_to_note'] ?? '') ?: null,
        ]);
```

mot:
```php
        $visitModel->update((int) $params['id'], [
            'visited_at'          => $_POST['visited_at'] ?? $visit['visited_at'],
            'raw_note'            => trim($_POST['raw_note'] ?? '') ?: null,
            'plus_notes'          => trim($_POST['plus_notes'] ?? '') ?: null,
            'minus_notes'         => trim($_POST['minus_notes'] ?? '') ?: null,
            'tips_notes'          => trim($_POST['tips_notes'] ?? '') ?: null,
            'price_level'         => $_POST['price_level'] ?? null,
            'would_return'        => $_POST['would_return'] ?? null,
            'suitable_for'        => trim($_POST['suitable_for'] ?? '') ?: null,
            'things_to_note'      => trim($_POST['things_to_note'] ?? '') ?: null,
            'approved_public_text'=> trim($_POST['approved_public_text'] ?? '') ?: null,
        ]);
```

- [ ] **Step 3: Verifiera att Visit::update() hanterar nya kolumnen**

Läs `app/Models/Visit.php` och kontrollera att `update()`-metoden dynamiskt bygger SET-klausulen från arrayen (inte hårdkodar kolumnnamn). Om den gör det behövs ingen ändring i modellen.

- [ ] **Step 4: Testa manuellt i webbläsaren**

1. Gå till `/adm/besok/{id}/redigera` för ett besök med godkänd text
2. Verifiera att textfältet visas med befintlig text
3. Ändra texten, klicka "Spara ändringar"
4. Gå tillbaka och verifiera att ändringen sparades

- [ ] **Step 5: Commit**

```bash
git add views/visits/edit.php app/Controllers/VisitController.php
git commit -m "feat: editable approved_public_text in visit edit form"
```

---

### Task 3: AI-prompter — jag/vi-form

**Files:**
- Modify: `app/Services/AiService.php:129-133` (sw()-metoden)
- Modify: `app/Services/AiService.php:443` (generatePlaceSeo meta)
- Modify: `app/Services/AiService.php:401` (FakeAiProvider fallback)

- [ ] **Step 1: Uppdatera sw()-metoden**

I `app/Services/AiService.php`, byt rad 129-133:

```php
    private function sw(): string
    {
        return ' Skriv alltid "ställplats" — använd ALDRIG "Stellplatz", "Stellplats" eller "stellplats".'
             . ' Om du nämner personnamn får du ENDAST använda Mattias och Ulrica — inga andra namn.';
    }
```

mot:

```php
    private function sw(): string
    {
        return ' Skriv alltid "ställplats" — använd ALDRIG "Stellplatz", "Stellplats" eller "stellplats".'
             . ' Skriv alltid i jag- eller vi-form. Använd aldrig tredje person eller personnamn som "Mattias och Ulrica".'
             . ' Exempel: "Vi stannade här..." inte "Mattias och Ulrica stannade här...".';
    }
```

- [ ] **Step 2: Fixa FakeAiProvider::generatePlaceSeo() meta-description**

I `app/Services/AiService.php`, byt rad 443:

```php
        $meta = "{$place['name']} — en {$typeLabel}{$country}. Recenserad av Mattias och Ulrica på Frizon of Sweden ur ett husbilsperspektiv.";
```

mot:

```php
        $meta = "{$place['name']} — en {$typeLabel}{$country} som vi besökt med vår husbil. Läs vår recension på Frizon of Sweden.";
```

- [ ] **Step 3: Fixa PublicController::placeDetail() fallback meta**

I `app/Controllers/PublicController.php`, byt rad 172:

```php
            ?? $place['name'] . ' — besökt av Mattias och Ulrica på Frizon of Sweden.';
```

mot:

```php
            ?? $place['name'] . ' — besökt med vår husbil Frizze. Läs vår recension på Frizon of Sweden.';
```

- [ ] **Step 4: Granska övriga hårdkodade tredjepersonstexter**

Sök igenom hela AiService.php efter "Mattias" och "Ulrica" — verifiera att alla förekomster som är i AI-prompter eller genererade texter nu är borta. Kontrollera att landningssidan/samarbeta-sidan INTE ändras (de ligger i PublicController och vyer, inte i AiService).

Run: `grep -n "Mattias\|Ulrica" app/Services/AiService.php`
Expected: Inga träffar

- [ ] **Step 5: Commit**

```bash
git add app/Services/AiService.php app/Controllers/PublicController.php
git commit -m "fix: AI prompts use jag/vi-form instead of third person"
```

---

### Task 4: Publik besökssida — route, controller, vy

**Files:**
- Modify: `routes/web.php:7` (lägg till ny route efter placeDetail)
- Modify: `app/Controllers/PublicController.php` (ny metod visitDetail)
- Create: `views/public/visit-detail.php`

- [ ] **Step 1: Lägg till route**

I `routes/web.php`, efter rad 7 (`$router->get('/platser/{slug}', 'PublicController', 'placeDetail');`), lägg till:

```php
    $router->get('/platser/{slug}/besok/{id}', 'PublicController', 'visitDetail');
```

- [ ] **Step 2: Lägg till visitDetail() i PublicController**

I `app/Controllers/PublicController.php`, lägg till ny metod efter `placeDetail()` (efter rad 253):

```php
    public function visitDetail(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place || !$place['public_allowed']) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
        }

        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached, vr.location_rating, vr.calmness_rating,
                   vr.service_rating, vr.value_rating, vr.return_value_rating
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.id = ? AND v.place_id = ? AND v.ready_for_publish = 1
        ');
        $stmt->execute([(int) $params['id'], $place['id']]);
        $visit = $stmt->fetch();

        if (!$visit) {
            http_response_code(404);
            echo '<h1>Besöket hittades inte</h1>';
            return;
        }

        $imageStmt = $this->pdo->prepare('
            SELECT * FROM visit_images WHERE visit_id = ? ORDER BY image_order ASC
        ');
        $imageStmt->execute([(int) $visit['id']]);
        $images = $imageStmt->fetchAll();

        $pageTitle = $place['name'] . ' — Besök ' . $visit['visited_at'] . ' | Frizon';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $ogImage = $appUrl . '/img/frizon-logo.png';
        if (!empty($images)) {
            $ogImage = $appUrl . '/uploads/cards/' . $images[0]['filename'];
        }

        $seoMeta = [
            'description' => mb_strimwidth($visit['approved_public_text'] ?? $place['name'], 0, 155, '...'),
            'og_url'      => $appUrl . '/platser/' . $place['slug'] . '/besok/' . $visit['id'],
            'og_image'    => $ogImage,
        ];

        $schemas = [];

        view('public/visit-detail', compact('place', 'visit', 'images', 'pageTitle', 'seoMeta', 'schemas'), 'public');
    }
```

- [ ] **Step 3: Skapa vyn views/public/visit-detail.php**

```php
<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
$typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];

$priceLevels = ['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'];
$wouldReturnLabels = ['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'];
?>

<article class="pub-detail">
    <div class="pub-detail__header">
        <a href="/platser/<?= htmlspecialchars($place['slug']) ?>" class="pub-detail__back">&larr; <?= htmlspecialchars($place['name']) ?></a>
        <h1 class="pub-detail__title"><?= htmlspecialchars($visit['visited_at']) ?></h1>
        <div class="pub-detail__meta">
            <span><?= htmlspecialchars($place['name']) ?></span>
            · <span><?= $typeLabel ?></span>
            <?php if ($visit['total_rating_cached']): ?>
                · <span>&#9733; <?= number_format((float)$visit['total_rating_cached'], 1) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Images grid -->
    <?php if (!empty($images)): ?>
        <div class="pub-visit__images">
            <?php foreach ($images as $i => $img): ?>
                <button type="button"
                        class="pub-visit__img-btn <?= $i === 0 ? 'pub-visit__img-btn--hero' : '' ?>"
                        data-lightbox
                        data-lightbox-src="/uploads/detail/<?= htmlspecialchars($img['filename']) ?>"
                        data-lightbox-caption="<?= htmlspecialchars($img['alt_text'] ?? '') ?>">
                    <img src="/uploads/<?= $i === 0 ? 'detail' : 'cards' ?>/<?= htmlspecialchars($img['filename']) ?>"
                         alt="<?= htmlspecialchars($img['alt_text'] ?? $place['name']) ?>"
                         width="<?= $i === 0 ? '1200' : '400' ?>"
                         height="<?= $i === 0 ? '900' : '300' ?>"
                         loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                </button>
            <?php endforeach; ?>
        </div>
        <script src="/js/lightbox.js" defer></script>
    <?php endif; ?>

    <!-- Visit text -->
    <?php if ($visit['approved_public_text']): ?>
        <div class="pub-detail__text">
            <?= nl2br(htmlspecialchars($visit['approved_public_text'])) ?>
        </div>
    <?php endif; ?>

    <!-- Ratings -->
    <?php if ($visit['total_rating_cached']): ?>
        <div class="pub-visit__ratings">
            <h3 class="pub-visit__section-title">Betyg</h3>
            <div class="pub-visit__rating-grid">
                <?php
                $ratingLabels = [
                    'location_rating' => 'Läge', 'calmness_rating' => 'Lugn',
                    'service_rating' => 'Service', 'value_rating' => 'Prisvärt',
                    'return_value_rating' => 'Återkomst',
                ];
                foreach ($ratingLabels as $field => $label):
                    if (!empty($visit[$field])):
                ?>
                    <div class="pub-visit__rating-item">
                        <span class="pub-visit__rating-label"><?= $label ?></span>
                        <span class="pub-visit__rating-value"><?= (int)$visit[$field] ?>/5</span>
                    </div>
                <?php endif; endforeach; ?>
            </div>
            <div class="pub-visit__rating-avg">
                <span class="pub-visit__rating-label">Snitt</span>
                <span class="pub-visit__rating-value pub-visit__rating-value--avg"><?= number_format((float)$visit['total_rating_cached'], 1) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Details -->
    <?php if ($visit['price_level'] || $visit['would_return'] || $visit['suitable_for']): ?>
        <div class="pub-visit__details">
            <?php if ($visit['price_level']): ?>
                <div class="pub-visit__detail-row">
                    <span class="pub-visit__detail-label">Prisnivå</span>
                    <span><?= htmlspecialchars($priceLevels[$visit['price_level']] ?? $visit['price_level']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($visit['would_return']): ?>
                <div class="pub-visit__detail-row">
                    <span class="pub-visit__detail-label">Skulle återvända</span>
                    <span><?= htmlspecialchars($wouldReturnLabels[$visit['would_return']] ?? $visit['would_return']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($visit['suitable_for']): ?>
                <div class="pub-visit__detail-row">
                    <span class="pub-visit__detail-label">Passar för</span>
                    <span><?= htmlspecialchars($visit['suitable_for']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Back link -->
    <div class="pub-detail__footer">
        <a href="/platser/<?= htmlspecialchars($place['slug']) ?>">&larr; Alla besök på <?= htmlspecialchars($place['name']) ?></a>
    </div>
</article>
```

- [ ] **Step 4: Lägg till CSS för besökssidan**

I den CSS-fil som hanterar publika sidor (sök efter `.pub-detail` för att hitta rätt fil), lägg till:

```css
/* Visit detail — image grid */
.pub-visit__images {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
}
.pub-visit__img-btn {
    border: 0;
    padding: 0;
    cursor: pointer;
    background: none;
    display: block;
}
.pub-visit__img-btn--hero {
    grid-column: 1 / -1;
}
.pub-visit__img-btn img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
}
.pub-visit__img-btn:not(.pub-visit__img-btn--hero) img {
    aspect-ratio: 1;
    object-fit: cover;
}

/* Visit detail — ratings */
.pub-visit__ratings {
    padding: var(--space-4);
    border-bottom: 1px solid var(--color-border);
}
.pub-visit__section-title {
    font-size: var(--text-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--color-text-muted);
    font-weight: var(--weight-semibold);
    margin-bottom: var(--space-3);
}
.pub-visit__rating-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2);
}
.pub-visit__rating-item {
    background: var(--color-bg-muted, #f8f9fa);
    border-radius: var(--radius-md);
    padding: var(--space-3);
}
.pub-visit__rating-label {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    display: block;
}
.pub-visit__rating-value {
    font-size: var(--text-base);
    font-weight: var(--weight-semibold);
    margin-top: 2px;
}
.pub-visit__rating-avg {
    background: var(--color-success-light, #f0fdf4);
    border-radius: var(--radius-md);
    padding: var(--space-3);
    margin-top: var(--space-2);
    text-align: center;
}
.pub-visit__rating-value--avg {
    font-size: var(--text-xl);
    font-weight: var(--weight-bold);
    color: var(--color-success);
}

/* Visit detail — metadata rows */
.pub-visit__details {
    padding: var(--space-4);
    border-bottom: 1px solid var(--color-border);
}
.pub-visit__detail-row {
    display: flex;
    justify-content: space-between;
    padding: var(--space-2) 0;
    border-bottom: 1px solid var(--color-border-light, #f0f0f0);
    font-size: var(--text-sm);
}
.pub-visit__detail-row:last-child {
    border-bottom: 0;
}
.pub-visit__detail-label {
    color: var(--color-text-muted);
}

/* Visit detail — footer */
.pub-detail__footer {
    padding: var(--space-4);
    text-align: center;
    font-size: var(--text-sm);
}
```

- [ ] **Step 5: Testa manuellt**

1. Gå till `/platser/{slug}/besok/{id}` för ett publicerat besök → sidan ska visa
2. Testa med opublicerat besök → 404
3. Testa med felaktigt place slug → 404
4. Kontrollera att bilder, betyg och text visas korrekt på mobil

- [ ] **Step 6: Commit**

```bash
git add routes/web.php app/Controllers/PublicController.php views/public/visit-detail.php public/css/*.css
git commit -m "feat: public visit detail page at /platser/{slug}/besok/{id}"
```

---

### Task 5: Publik platssida — besökskort med ihopfällning

**Files:**
- Modify: `views/public/place-detail.php:83-99` (visit summaries-sektionen)
- Modify: `views/public/place-detail.php:14-24` (meta — lägg till besöksräknare)

- [ ] **Step 1: Lägg till besöksräknare i platshuvudet**

I `views/public/place-detail.php`, efter avgRating-blocket (rad 21) och före toplist-checken (rad 22), lägg till:

```php
            <?php if (count($visits) > 0): ?>
                · <span>Besökt <?= count($visits) ?> <?= count($visits) === 1 ? 'gång' : 'gånger' ?></span>
            <?php endif; ?>
```

- [ ] **Step 2: Lägg till preview-bild under kartan**

I `views/public/place-detail.php`, efter map-diven (rad 31, efter stängande `</div>` för place-map) och före description-blocket, lägg till:

```php
    <!-- Preview image -->
    <?php if (!empty($place['preview_image_id'])):
        $prevStmt = $GLOBALS['pdo']->prepare('SELECT filename, alt_text FROM visit_images WHERE id = ?');
        $prevStmt->execute([$place['preview_image_id']]);
        $previewImg = $prevStmt->fetch();
        if ($previewImg):
    ?>
        <div class="pub-detail__preview-img">
            <img src="/uploads/detail/<?= htmlspecialchars($previewImg['filename']) ?>"
                 alt="<?= htmlspecialchars($previewImg['alt_text'] ?? $place['name']) ?>"
                 width="1200" height="900"
                 loading="eager">
        </div>
    <?php endif; endif; ?>
```

Obs: Kontrollera hur PDO-instansen nås i vyer. Om `$GLOBALS['pdo']` inte fungerar, skicka med preview-bilden från controllern istället — se step 2b nedan.

- [ ] **Step 2b: Alternativt — hämta preview-bild i controllern**

I `app/Controllers/PublicController.php`, i `placeDetail()`, innan `view()`-anropet (rad 252), lägg till:

```php
        $previewImage = null;
        if ($place['preview_image_id']) {
            $prevStmt = $this->pdo->prepare('SELECT filename, alt_text FROM visit_images WHERE id = ?');
            $prevStmt->execute([$place['preview_image_id']]);
            $previewImage = $prevStmt->fetch() ?: null;
        }
```

Och lägg till `'previewImage'` i compact()-anropet. Uppdatera då vyn till att använda `$previewImage` direkt istället för att köra query i vyn.

Använd detta alternativ (step 2b) istället för step 2 — det är renare.

- [ ] **Step 3: Byt ut visit summaries till senaste besök + ihopfällning**

I `views/public/place-detail.php`, byt ut hela visit-sektionen (rad 83-99):

```php
    <?php if (!empty($visits)): ?>
        <div class="pub-detail__visits">
            <!-- Latest visit -->
            <?php $latest = $visits[0]; ?>
            <div class="pub-visit-card__section-title">Senaste besöket</div>
            <a href="/platser/<?= htmlspecialchars($place['slug']) ?>/besok/<?= $latest['id'] ?>" class="pub-visit-card pub-visit-card--linked">
                <div class="pub-visit-card__top">
                    <span class="pub-visit-card__date"><?= htmlspecialchars($latest['visited_at']) ?></span>
                    <span class="pub-visit-card__right">
                        <?php if ($latest['total_rating_cached']): ?>
                            <span class="pub-visit-card__rating">&#9733; <?= number_format((float)$latest['total_rating_cached'], 1) ?></span>
                        <?php endif; ?>
                        <span class="pub-visit-card__chevron">›</span>
                    </span>
                </div>
                <?php if ($latest['approved_public_text']): ?>
                    <p class="pub-visit-card__text"><?= nl2br(htmlspecialchars(mb_strimwidth($latest['approved_public_text'], 0, 250, '...'))) ?></p>
                <?php endif; ?>
                <?php
                    $imgCountStmt = $this->pdo ?? null;
                    // Count images for this visit
                    $latestImgCount = 0;
                    foreach ($images as $img) {
                        if ($img['visit_id'] === $latest['id']) $latestImgCount++;
                    }
                ?>
                <?php if ($latestImgCount > 0): ?>
                    <span class="pub-visit-card__img-count"><?= $latestImgCount ?> <?= $latestImgCount === 1 ? 'bild' : 'bilder' ?></span>
                <?php endif; ?>
            </a>

            <!-- Older visits -->
            <?php if (count($visits) > 1): ?>
                <details class="pub-detail__older-visits">
                    <summary class="pub-detail__older-summary">
                        <span>Tidigare besök (<?= count($visits) - 1 ?>)</span>
                        <span class="pub-detail__older-toggle">Visa</span>
                    </summary>
                    <?php for ($i = 1; $i < count($visits); $i++): $v = $visits[$i]; ?>
                        <a href="/platser/<?= htmlspecialchars($place['slug']) ?>/besok/<?= $v['id'] ?>" class="pub-visit-card pub-visit-card--linked pub-visit-card--compact">
                            <div class="pub-visit-card__top">
                                <span class="pub-visit-card__date"><?= htmlspecialchars($v['visited_at']) ?></span>
                                <span class="pub-visit-card__right">
                                    <?php if ($v['total_rating_cached']): ?>
                                        <span class="pub-visit-card__rating">&#9733; <?= number_format((float)$v['total_rating_cached'], 1) ?></span>
                                    <?php endif; ?>
                                    <span class="pub-visit-card__chevron">›</span>
                                </span>
                            </div>
                            <?php if ($v['approved_public_text']): ?>
                                <p class="pub-visit-card__text"><?= nl2br(htmlspecialchars(mb_strimwidth($v['approved_public_text'], 0, 150, '...'))) ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endfor; ?>
                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>
```

- [ ] **Step 4: Skicka med visit_images per besök från controllern**

I `app/Controllers/PublicController.php`, i `placeDetail()`, efter att `$images` hämtats (rad 140), indexera bilder per visit_id:

```php
        // Index image counts per visit
        $visitImageCounts = [];
        foreach ($images as $img) {
            $vid = $img['visit_id'];
            $visitImageCounts[$vid] = ($visitImageCounts[$vid] ?? 0) + 1;
        }
```

Lägg till `'visitImageCounts'` i compact()-anropet. I vyn, byt ut image-räkningen mot `$visitImageCounts[$latest['id']] ?? 0`.

- [ ] **Step 5: Lägg till CSS**

```css
/* Place detail — visit cards */
.pub-visit-card__section-title {
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--color-text-muted);
    font-weight: var(--weight-semibold);
    margin-bottom: var(--space-2);
}
.pub-visit-card--linked {
    display: block;
    text-decoration: none;
    color: inherit;
    background: var(--color-bg-muted, #f8f9fa);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    margin-bottom: var(--space-2);
}
.pub-visit-card__top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-2);
}
.pub-visit-card__date {
    font-weight: var(--weight-semibold);
}
.pub-visit-card__right {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.pub-visit-card__chevron {
    color: var(--color-text-muted);
    font-size: var(--text-lg);
}
.pub-visit-card__text {
    font-size: var(--text-sm);
    line-height: var(--leading-relaxed);
    color: var(--color-text-muted);
    margin: 0;
}
.pub-visit-card__img-count {
    display: inline-block;
    margin-top: var(--space-2);
    font-size: var(--text-xs);
    color: var(--color-text-muted);
}

/* Older visits — collapsible */
.pub-detail__older-visits {
    margin-top: var(--space-3);
}
.pub-detail__older-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: var(--text-sm);
    font-weight: var(--weight-semibold);
    color: var(--color-text-muted);
    cursor: pointer;
    padding: var(--space-2) 0;
    list-style: none;
}
.pub-detail__older-summary::-webkit-details-marker {
    display: none;
}
.pub-detail__older-toggle {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
}
.pub-detail__older-visits[open] .pub-detail__older-toggle::after {
    content: '';
}
.pub-visit-card--compact {
    padding: var(--space-3);
    margin-top: var(--space-2);
}
.pub-visit-card--compact .pub-visit-card__text {
    font-size: var(--text-xs);
}
```

- [ ] **Step 6: Testa manuellt**

1. Publik platssida med 1 besök → visar som "Senaste besöket", ingen "Tidigare besök"
2. Platssida med 3+ besök → senaste som kort, "Tidigare besök (2)" ihopfälld
3. Klicka på besökskort → navigerar till besökssida
4. Verifiera på mobil att touch-targets är tillräckligt stora

- [ ] **Step 7: Commit**

```bash
git add views/public/place-detail.php app/Controllers/PublicController.php public/css/*.css
git commit -m "feat: visit cards with collapsible history on public place page"
```

---

### Task 6: Admin platssida — publiceringsstatus + "Använd som platsbild"

**Files:**
- Modify: `views/places/show.php:50-63` (visit-korten)
- Modify: `app/Controllers/PlaceController.php` (ny metod setPreviewImage)
- Modify: `routes/web.php` (ny route)
- Modify: `views/visits/show.php` (lägg till "Använd som platsbild"-knapp)

- [ ] **Step 1: Uppdatera admin besökslistan med publiceringsstatus**

I `views/places/show.php`, byt ut besökslistan (rad 50-63):

```php
    <?php if (empty($visits)): ?>
        <p class="text-muted">Inga besök ännu.</p>
    <?php else: ?>
        <?php foreach ($visits as $visit): ?>
            <a href="/adm/besok/<?= $visit['id'] ?>" class="visit-card mb-3" style="display:block; text-decoration:none; color:inherit; border-left:3px solid <?= $visit['ready_for_publish'] ? 'var(--color-success)' : 'var(--color-border)' ?>;">
                <div class="flex-between">
                    <div>
                        <span class="visit-card__date"><?= htmlspecialchars($visit['visited_at']) ?></span>
                        <?php if ($visit['total_rating_cached']): ?>
                            <span class="visit-card__rating" style="margin-left:var(--space-2);">&#9733; <?= number_format((float) $visit['total_rating_cached'], 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:var(--space-2);">
                        <span class="text-sm" style="color:<?= $visit['ready_for_publish'] ? 'var(--color-success)' : 'var(--color-text-muted)' ?>; font-weight:600;">
                            <?= $visit['ready_for_publish'] ? 'Pub' : 'Ej pub' ?>
                        </span>
                        <span style="color:var(--color-text-muted);">›</span>
                    </div>
                </div>
                <?php if ($visit['raw_note']): ?>
                    <p class="visit-card__note text-sm mt-2"><?= nl2br(htmlspecialchars(mb_strimwidth($visit['raw_note'], 0, 200, '...'))) ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
```

- [ ] **Step 2: Hämta ready_for_publish i PlaceController::show()**

Kontrollera att PlaceController::show() redan hämtar `ready_for_publish` i sin visit-query. Om inte, lägg till `v.ready_for_publish` i SELECT.

- [ ] **Step 3: Lägg till route för setPreviewImage**

I `routes/web.php`, efter place-routes (rad 41), lägg till:

```php
    $router->post('/adm/platser/{slug}/preview-image', 'PlaceController', 'setPreviewImage');
```

- [ ] **Step 4: Lägg till setPreviewImage() i PlaceController**

```php
    public function setPreviewImage(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) { http_response_code(404); return; }

        $imageId = (int) ($_POST['image_id'] ?? 0);

        // Verify image belongs to a visit on this place
        $stmt = $this->pdo->prepare('
            SELECT vi.id FROM visit_images vi
            JOIN visits v ON v.id = vi.visit_id
            WHERE vi.id = ? AND v.place_id = ?
        ');
        $stmt->execute([$imageId, $place['id']]);
        if (!$stmt->fetch()) {
            flash('error', 'Bilden hör inte till denna plats.');
            redirect('/adm/platser/' . $params['slug']);
            return;
        }

        $this->pdo->prepare('UPDATE places SET preview_image_id = ?, updated_at = NOW() WHERE id = ?')
                   ->execute([$imageId, $place['id']]);

        SecurityAudit::log($this->pdo, 'place.preview_image_set', [
            'place_id' => $place['id'],
            'image_id' => $imageId,
        ], Auth::userId());

        flash('success', 'Platsbild uppdaterad.');
        redirect('/adm/platser/' . $params['slug']);
    }
```

- [ ] **Step 5: Lägg till "Använd som platsbild"-knapp på besöksvisningen**

I `views/visits/show.php`, i image-loopen (rad 57-92), efter rotera-knapparna (rad 72, stängande `</div>` för img-manage__tools), lägg till:

```php
                    <form method="POST" action="/adm/platser/<?= htmlspecialchars($visit['place_slug']) ?>/preview-image" style="display:inline;">
                        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                        <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn--sm" title="Använd som platsbild" aria-label="Använd som platsbild"
                            <?= ($place['preview_image_id'] ?? null) == $img['id'] ? 'disabled style="opacity:0.5;"' : '' ?>>
                            📌
                        </button>
                    </form>
```

Obs: Kontrollera att `$place`-datan finns tillgänglig i visits/show.php. Om inte, hämta den i VisitController::show() och skicka med.

- [ ] **Step 6: Skicka med place-data till visit show-vyn**

I `app/Controllers/VisitController.php`, i `show()` (rad 118-133), hämta plats-info:

```php
        $placeModel = new Place($this->pdo);
        $place = $placeModel->findById((int) $visit['place_id']);
```

Lägg till `'place'` i compact()-anropet på rad 132.

- [ ] **Step 7: Testa manuellt**

1. Admin platssida: besök visar "Pub"/"Ej pub" med grön/grå vänsterkant
2. Klicka på besökskort → navigerar till besöksvisning
3. På besöksvisning: klicka 📌 på en bild → redirect tillbaka till platssidan
4. Verifiera att preview_image_id satts i databasen
5. Kontrollera publika platssidan — preview-bilden visas under kartan

- [ ] **Step 8: Commit**

```bash
git add views/places/show.php views/visits/show.php app/Controllers/PlaceController.php app/Controllers/VisitController.php routes/web.php
git commit -m "feat: publish status on admin visits + set preview image"
```

---

### Task 7: Service worker cache-bump

**Files:**
- Modify: service worker JS-fil (sök efter `CACHE_NAME` eller `frizon-v`)

- [ ] **Step 1: Hitta och bumpa cache-namn**

Run: `grep -r 'frizon-v' public/`
Byt `frizon-v9` (eller aktuellt värde) till `frizon-v10`.

- [ ] **Step 2: Commit**

```bash
git add public/sw.js
git commit -m "chore: bump service worker cache to v10"
```
