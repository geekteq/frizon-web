# Place description & visit products — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop visit AI approval from overwriting `places.default_public_text`, add per-visit Amazon product associations alongside the existing place-level ones, and surface both on the public place page.

**Architecture:** Surgical changes to existing controllers/models. New `visit_products` table mirrors the existing `place_products`. Visit-edit gets the same product picker partial as place-edit. Public place page renders two sections: place-level recommendations + per-visit products.

**Tech Stack:** PHP 8 + PDO/MySQL, plain PHP views, light JS.

## Spec deviation note

The spec (`docs/superpowers/specs/2026-05-03-place-description-and-visit-products-design.md`) section 2 proposes a polymorphic `ai_drafts.target_type` column and a draft+approve flow for place AI. **The existing codebase already implements place AI more simply** — `AiController::generatePlaceDraft` returns text directly, `views/places/edit.php` injects it into the textarea, and saving the place form is the implicit approval. We keep this simpler model and only fix its inputs (use published visits + DB `default_public_text`, never the user-edited textarea seed) to match the spec's intent. No `ai_drafts` schema change needed; migration `019_ai_drafts_target.sql` from the spec is dropped.

## File structure

**New files:**
- `database/migrations/018_visit_products.sql` — new join table
- `app/Models/VisitProduct.php` — sync/get for `visit_products`
- `views/partials/product-picker.php` — shared product checkbox picker (extracted from `views/places/edit.php`)
- `tests/test_place_description_isolation.php` — regression test for the auto-overwrite removal
- `tests/test_visit_products.php` — model CRUD test
- `tests/test_place_ai_inputs.php` — verifies place AI prompt context only contains published visits

**Modified files:**
- `app/Controllers/AiController.php` — remove auto-overwrite block (lines 277-282); change `generatePlaceDraft` input gathering
- `app/Controllers/VisitController.php` — accept `product_ids` in `store`/`update`
- `app/Controllers/PublicController.php` — load per-visit products, pass to view
- `app/Models/Visit.php` — add helper to load visit with associated products (or use VisitProduct directly)
- `views/places/edit.php` — replace inline product picker with `partials/product-picker.php` include
- `views/visits/edit.php` — include same product picker
- `views/visits/create.php` — include same product picker
- `views/public/place-detail.php` — render two product sections
- `database/deploy-prod.sh` — append `018_visit_products.sql` to migration list

**Unchanged:**
- `database/migrations/010_place_products.sql` — semantics shift but schema unchanged
- `app/Models/AmazonProduct.php` — keep `getByPlaceId` / `syncPlaceProducts` as-is

---

## Task 1: Add `visit_products` migration

**Files:**
- Create: `database/migrations/018_visit_products.sql`
- Modify: `database/deploy-prod.sh` (migration list, append `018_visit_products.sql`)

- [ ] **Step 1: Create the migration**

```sql
-- 018_visit_products.sql
-- Per-visit Amazon product associations. Mirrors place_products but scoped
-- to a single visit ("what we used at this specific stay").

CREATE TABLE IF NOT EXISTS visit_products (
    visit_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    note        VARCHAR(255) DEFAULT NULL,
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (visit_id, product_id),
    FOREIGN KEY (visit_id)   REFERENCES visits(id)           ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES amazon_products(id)  ON DELETE CASCADE,
    INDEX idx_visit_products_visit   (visit_id),
    INDEX idx_visit_products_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Append the new migration to `deploy-prod.sh`**

Open `database/deploy-prod.sh` and add the new file to the `MIGRATIONS` array, after `017_frizze_document_interpretations.sql`:

```bash
    017_frizze_document_interpretations.sql
    018_visit_products.sql
)
```

- [ ] **Step 3: Apply locally**

Run against the local dev DB (replace creds with whatever is in `.env`):

```bash
mysql -u <user> -p <db> < database/migrations/018_visit_products.sql
```

Expected: no output, exit 0.

Verify:

```bash
mysql -u <user> -p <db> -e "DESCRIBE visit_products;"
```

Expected: 4 columns (visit_id, product_id, note, sort_order).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/018_visit_products.sql database/deploy-prod.sh
git commit -m "Add visit_products table"
```

---

## Task 2: Remove auto-overwrite of place description on visit approval

**Files:**
- Create: `tests/test_place_description_isolation.php`
- Modify: `app/Controllers/AiController.php:277-282`

- [ ] **Step 1: Write the regression test**

Create `tests/test_place_description_isolation.php`:

```php
<?php
/**
 * Regression test: approving a visit AI draft must NOT overwrite the
 * place's default_public_text. Reads AiController source to verify the
 * removed code is gone — keeps the test infrastructure-free.
 *
 * Run: php tests/test_place_description_isolation.php
 */

$source = file_get_contents(__DIR__ . '/../app/Controllers/AiController.php');
if ($source === false) {
    fwrite(STDERR, "Could not read AiController.php\n");
    exit(1);
}

$passed = 0;
$failed = 0;

function check(string $name, bool $cond): void {
    global $passed, $failed;
    if ($cond) { echo "PASS: {$name}\n"; $passed++; }
    else        { echo "FAIL: {$name}\n"; $failed++; }
}

// The string we removed from approveDraft:
$forbiddenSql = 'UPDATE places SET default_public_text';

check(
    'approveDraft does not write to places.default_public_text',
    !str_contains($source, $forbiddenSql)
);

// SEO regeneration after approval should still be present (different feature).
check(
    'SEO regeneration block still present',
    str_contains($source, 'generatePlaceSeo')
);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 2: Run the test, expect FAIL**

```bash
php tests/test_place_description_isolation.php
```

Expected: `FAIL: approveDraft does not write to places.default_public_text` (the forbidden SQL is still in the file).

- [ ] **Step 3: Remove the auto-overwrite block**

Open `app/Controllers/AiController.php`. Find the block at lines 277-282:

```php
        // Also copy to the place's default_public_text
        $stmt = $this->pdo->prepare('
            UPDATE places SET default_public_text = ?, updated_at = NOW()
            WHERE id = (SELECT place_id FROM visits WHERE id = ?)
        ');
        $stmt->execute([$draft['draft_text'], $visitId]);
```

Delete those 6 lines (including the comment) plus the blank line before, leaving the SEO regeneration block intact directly after the visit update.

- [ ] **Step 4: Run the test, expect PASS**

```bash
php tests/test_place_description_isolation.php
```

Expected:
```
PASS: approveDraft does not write to places.default_public_text
PASS: SEO regeneration block still present

2 passed, 0 failed
```

- [ ] **Step 5: Commit**

```bash
git add tests/test_place_description_isolation.php app/Controllers/AiController.php
git commit -m "Stop visit AI approval from overwriting place description"
```

---

## Task 3: Restrict place AI input to published visits + DB description

**Files:**
- Create: `tests/test_place_ai_inputs.php`
- Modify: `app/Controllers/AiController.php` — `generatePlaceDraft` method (lines ~111-185)

Today the method enriches context with the **most recent visit regardless of `ready_for_publish`**, and uses `current_text` from the request body (the user-edited textarea) as `raw_note`. The spec wants only published visits and the DB-stored `default_public_text`.

- [ ] **Step 1: Write the test**

Create `tests/test_place_ai_inputs.php`:

```php
<?php
/**
 * Verifies generatePlaceDraft uses (a) places.default_public_text from DB,
 * not user-supplied textarea content, and (b) only published visits.
 * Source-level inspection — keeps the test infrastructure-free.
 *
 * Run: php tests/test_place_ai_inputs.php
 */

$source = file_get_contents(__DIR__ . '/../app/Controllers/AiController.php');
if ($source === false) { fwrite(STDERR, "read failed\n"); exit(1); }

// Isolate the generatePlaceDraft method body.
$start = strpos($source, 'public function generatePlaceDraft');
$end   = strpos($source, 'public function generatePlaceSeo');
if ($start === false || $end === false) {
    fwrite(STDERR, "Could not locate generatePlaceDraft method\n");
    exit(1);
}
$method = substr($source, $start, $end - $start);

$passed = 0;
$failed = 0;
function check(string $name, bool $cond): void {
    global $passed, $failed;
    if ($cond) { echo "PASS: {$name}\n"; $passed++; }
    else        { echo "FAIL: {$name}\n"; $failed++; }
}

check(
    'Filters visits by ready_for_publish = 1',
    str_contains($method, 'ready_for_publish = 1')
);
check(
    'Does not read user-supplied current_text from request body',
    !str_contains($method, "current_text")
);
check(
    'Does not call file_get_contents on php://input inside this method',
    !str_contains($method, "php://input")
);
check(
    'Uses default_public_text from the loaded place row',
    str_contains($method, "\$place['default_public_text']")
);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 2: Run the test, expect FAIL**

```bash
php tests/test_place_ai_inputs.php
```

Expected: at least three of the four assertions FAIL (current code uses `current_text` from `php://input` and doesn't filter by `ready_for_publish`).

- [ ] **Step 3: Rewrite the method body**

Open `app/Controllers/AiController.php`. Replace the body of `generatePlaceDraft` (the part after the `if (!$place)` check through the `echo json_encode(...)`) with:

```php
        // Build context from place + all PUBLISHED visits (approved_public_text only).
        $context = [
            'place_name'   => $place['name'],
            'place_type'   => $place['place_type'],
            'country_code' => $place['country_code'],
            // Seed text is the human-curated description currently on the place.
            'raw_note'     => $place['default_public_text'] ?? '',
        ];

        // Aggregate approved review text from every published visit on this place.
        $stmt = $this->pdo->prepare('
            SELECT approved_public_text, visited_at
            FROM visits
            WHERE place_id = ? AND ready_for_publish = 1
            ORDER BY visited_at ASC
        ');
        $stmt->execute([$place['id']]);
        $visits = $stmt->fetchAll();

        $reviewExcerpts = [];
        foreach ($visits as $v) {
            $text = trim((string) ($v['approved_public_text'] ?? ''));
            if ($text !== '') {
                $reviewExcerpts[] = '[' . $v['visited_at'] . '] ' . $text;
            }
        }
        if (!empty($reviewExcerpts)) {
            // AiService reads `plus_notes` as additional positive context;
            // we reuse it for the cumulative review summary.
            $context['plus_notes'] = implode("\n\n", $reviewExcerpts);
        }

        try {
            $aiService = new AiService();
            $draftText = $aiService->generateDraft($context);
        } catch (RuntimeException $e) {
            error_log('AI place draft generation failed for place ' . ($place['id'] ?? 'unknown') . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'AI-tjänsten kunde inte generera text just nu. Försök igen senare.']);
            return;
        }

        echo json_encode(['success' => true, 'text' => $draftText]);
```

This replaces the previous block that loaded the latest visit (regardless of publish state), pulled `current_text` from `php://input`, and used the latest visit's `plus_notes` / `tips_notes` directly.

- [ ] **Step 4: Update the JS to stop sending `current_text`**

Open `views/places/edit.php`. In the AI generate fetch block (around line 204-211), change:

```js
            fetch('/adm/platser/<?= htmlspecialchars($p['slug']) ?>/ai/generera', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf ? csrf.value : ''
                },
                body: JSON.stringify({ current_text: descField.value })
            })
```

to:

```js
            fetch('/adm/platser/<?= htmlspecialchars($p['slug']) ?>/ai/generera', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrf ? csrf.value : ''
                }
            })
```

(Body and `Content-Type` no longer needed — server reads only the place from the URL.)

- [ ] **Step 5: Run the test, expect PASS**

```bash
php tests/test_place_ai_inputs.php
```

Expected: all 4 PASS.

- [ ] **Step 6: Smoke test in browser**

```bash
./serve.sh
```

Open `/adm/platser/<some-slug>/redigera`, click "Brodera ut text", confirm: textarea fills with AI text, no JS console errors, no PHP error log entries.

- [ ] **Step 7: Commit**

```bash
git add tests/test_place_ai_inputs.php app/Controllers/AiController.php views/places/edit.php
git commit -m "Use only published visits for place AI draft input"
```

---

## Task 4: VisitProduct model

**Files:**
- Create: `app/Models/VisitProduct.php`
- Create: `tests/test_visit_products.php`

- [ ] **Step 1: Write the failing test**

Create `tests/test_visit_products.php`:

```php
<?php
/**
 * Test: VisitProduct model — sync, get, delete-cascade semantics.
 * Uses an in-memory SQLite-shaped schema is overkill here; we use the
 * existing dev MySQL via `getenv` connection params, mirroring the
 * lightweight style of other tests.
 *
 * Run: php tests/test_visit_products.php
 *
 * Requires: a DB connection. Set DB_HOST, DB_NAME, DB_USER, DB_PASS env vars,
 * or a populated `.env` (loaded by bootstrap).
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/Models/VisitProduct.php';

global $pdo;
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "No PDO connection — check .env\n");
    exit(2);
}

$passed = 0;
$failed = 0;
function check(string $name, bool $cond): void {
    global $passed, $failed;
    if ($cond) { echo "PASS: {$name}\n"; $passed++; }
    else        { echo "FAIL: {$name}\n"; $failed++; }
}

// Pick an arbitrary visit + two product IDs that exist
$visitId   = (int) $pdo->query('SELECT id FROM visits LIMIT 1')->fetchColumn();
$productRows = $pdo->query('SELECT id FROM amazon_products LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);

if ($visitId === 0 || count($productRows) < 2) {
    echo "SKIP: Need at least 1 visit and 2 amazon_products in dev DB to run this test.\n";
    exit(0);
}

$model = new VisitProduct($pdo);

// Clean state
$model->syncForVisit($visitId, []);
check('Empty state after clear', count($model->findByVisit($visitId)) === 0);

// Sync two products
$model->syncForVisit($visitId, [(int) $productRows[0], (int) $productRows[1]]);
$got = $model->findByVisit($visitId);
check('Two products attached', count($got) === 2);

// Re-sync with one — second should be removed
$model->syncForVisit($visitId, [(int) $productRows[0]]);
$got = $model->findByVisit($visitId);
check('Re-sync narrows to one product', count($got) === 1);
check('Remaining product is the first one', (int) $got[0]['id'] === (int) $productRows[0]);

// Cleanup
$model->syncForVisit($visitId, []);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 2: Run the test, expect FAIL**

```bash
php tests/test_visit_products.php
```

Expected: `Class "VisitProduct" not found` or include error.

- [ ] **Step 3: Implement the model**

Create `app/Models/VisitProduct.php`:

```php
<?php

declare(strict_types=1);

class VisitProduct
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * All published Amazon products linked to a visit, ordered by sort_order.
     * Returns the same shape as AmazonProduct::getByPlaceId.
     */
    public function findByVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ap.*
            FROM amazon_products ap
            JOIN visit_products vp ON vp.product_id = ap.id
            WHERE vp.visit_id = ? AND ap.is_published = 1
            ORDER BY vp.sort_order ASC, ap.title ASC
        ');
        $stmt->execute([$visitId]);
        return $stmt->fetchAll();
    }

    /**
     * Replace all product links for a visit. Pass [] to clear.
     */
    public function syncForVisit(int $visitId, array $productIds): void
    {
        $this->pdo->prepare('DELETE FROM visit_products WHERE visit_id = ?')
                  ->execute([$visitId]);

        if (empty($productIds)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO visit_products (visit_id, product_id, sort_order) VALUES (?, ?, ?)'
        );
        foreach (array_values($productIds) as $order => $productId) {
            $stmt->execute([$visitId, (int) $productId, $order]);
        }
    }

    /**
     * Loads visit_products grouped by visit_id for a list of visits.
     * Returns: [visit_id => [product_row, ...], ...]
     */
    public function findByVisitIds(array $visitIds): array
    {
        if (empty($visitIds)) return [];

        $placeholders = implode(',', array_fill(0, count($visitIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT vp.visit_id, ap.*
            FROM amazon_products ap
            JOIN visit_products vp ON vp.product_id = ap.id
            WHERE vp.visit_id IN ($placeholders) AND ap.is_published = 1
            ORDER BY vp.visit_id, vp.sort_order ASC, ap.title ASC
        ");
        $stmt->execute(array_map('intval', $visitIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $vid = (int) $row['visit_id'];
            unset($row['visit_id']);
            $grouped[$vid][] = $row;
        }
        return $grouped;
    }
}
```

- [ ] **Step 4: Run the test, expect PASS**

```bash
php tests/test_visit_products.php
```

Expected: `3-4 passed, 0 failed` (or `SKIP` if dev DB has no visits/products yet — that's fine, exit 0).

- [ ] **Step 5: Commit**

```bash
git add app/Models/VisitProduct.php tests/test_visit_products.php
git commit -m "Add VisitProduct model"
```

---

## Task 5: Extract product picker partial

**Files:**
- Create: `views/partials/product-picker.php`
- Modify: `views/places/edit.php`

The current product picker is inline in `views/places/edit.php` (lines 111-141). Extract it so visit-edit can reuse it.

- [ ] **Step 1: Create the partial**

Create `views/partials/product-picker.php`:

```php
<?php
/**
 * Reusable product picker (checkbox list).
 *
 * Required vars in scope:
 *   - $allProducts        array  list of amazon_product rows
 *   - $attachedProductIds int[]  currently attached product IDs
 *   - $pickerTitle        string heading text
 *   - $pickerDescription  string short helper text under the heading
 *
 * Posts as `product_ids[]` checkboxes — controllers handle persistence.
 */
if (empty($allProducts)) return;
?>
<section style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <h2 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">
        <?= htmlspecialchars($pickerTitle) ?>
    </h2>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">
        <?= htmlspecialchars($pickerDescription) ?>
    </p>
    <div style="display:flex; flex-direction:column; gap:var(--space-2);">
        <?php foreach ($allProducts as $prod): ?>
            <label style="display:flex; align-items:center; gap:var(--space-3); cursor:pointer; padding:var(--space-2) 0;">
                <input type="checkbox"
                       name="product_ids[]"
                       value="<?= (int) $prod['id'] ?>"
                       <?= in_array((int) $prod['id'], $attachedProductIds ?? [], true) ? 'checked' : '' ?>>
                <?php if ($prod['image_path']): ?>
                    <img src="/uploads/amazon/<?= htmlspecialchars($prod['image_path']) ?>"
                         alt="" width="40" height="40"
                         style="width:40px; height:40px; object-fit:contain; background:#f5f5f4; border-radius:var(--radius-sm); flex-shrink:0;">
                <?php endif; ?>
                <span style="font-size:var(--text-sm);"><?= htmlspecialchars($prod['title']) ?></span>
                <?php if ($prod['category']): ?>
                    <span style="font-size:var(--text-xs); color:var(--color-text-muted); margin-left:auto;">
                        <?= htmlspecialchars($prod['category']) ?>
                    </span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
</section>
```

- [ ] **Step 2: Replace the inline block in `views/places/edit.php`**

Find the block at lines 111-141 (`<?php if (!empty($allProducts)): ?>` … `<?php endif; ?>`). Replace it with:

```php
<?php
$pickerTitle       = 'Rekommenderat för platsen';
$pickerDescription = 'Kuraterade produkter som visas på den publika platssidan, oavsett besök.';
include dirname(__DIR__) . '/partials/product-picker.php';
?>
```

- [ ] **Step 3: Smoke test**

```bash
./serve.sh
```

Open `/adm/platser/<some-slug>/redigera`. Expect: same product list, new heading "Rekommenderat för platsen", new description copy. Selecting/deselecting still saves correctly.

- [ ] **Step 4: Commit**

```bash
git add views/partials/product-picker.php views/places/edit.php
git commit -m "Extract product picker into shared partial"
```

---

## Task 6: Wire visit_products into visit-edit / visit-create

**Files:**
- Modify: `app/Controllers/VisitController.php` — `create`, `store`, `edit`, `update`
- Modify: `views/visits/edit.php`
- Modify: `views/visits/create.php`

- [ ] **Step 1: Pass product data to visit-create view**

Open `app/Controllers/VisitController.php`. In `create()` (around line 28-44), add right before the `view(...)` call:

```php
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $allProducts        = (new AmazonProduct($this->pdo))->allPublished();
        $attachedProductIds = [];
```

Update the final view call to include the new vars:

```php
        view('visits/create', compact('p', 'pageTitle', 'suitableForSuggestions', 'allProducts', 'attachedProductIds'));
```

- [ ] **Step 2: Pass product data to visit-edit view**

In `edit()` (around line 138-152), add before the `view(...)` call:

```php
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        require_once dirname(__DIR__) . '/Models/VisitProduct.php';
        $allProducts        = (new AmazonProduct($this->pdo))->allPublished();
        $attachedProductIds = array_map(
            'intval',
            array_column((new VisitProduct($this->pdo))->findByVisit((int) $params['id']), 'id')
        );
```

Update the view call:

```php
        view('visits/edit', compact('visit', 'ratings', 'pageTitle', 'suitableForSuggestions', 'allProducts', 'attachedProductIds'));
```

- [ ] **Step 3: Persist `product_ids[]` in `store`**

In `store()` (around line 46-116), add after the `$ratingModel->save(...)` call (around line 78) but before image handling:

```php
        require_once dirname(__DIR__) . '/Models/VisitProduct.php';
        $productIds = array_map('intval', (array) ($_POST['product_ids'] ?? []));
        (new VisitProduct($this->pdo))->syncForVisit($visitId, $productIds);
```

- [ ] **Step 4: Persist `product_ids[]` in `update`**

In `update()` (around line 154-191), add after the `$ratingModel->save(...)` call:

```php
        require_once dirname(__DIR__) . '/Models/VisitProduct.php';
        $productIds = array_map('intval', (array) ($_POST['product_ids'] ?? []));
        (new VisitProduct($this->pdo))->syncForVisit((int) $params['id'], $productIds);
```

- [ ] **Step 5: Add product picker to visit-edit view**

Open `views/visits/edit.php`. Insert the partial include directly before the closing `</form>` (after line 114, the "Spara ändringar" button line, but before `</form>`). The form already submits to the controller that now handles `product_ids[]`.

Replace this block (lines 113-115):

```php
    <button type="submit" class="btn btn-primary btn--full">Spara ändringar</button>
</form>
```

with:

```php
    <?php
    $pickerTitle       = 'Produkter vi använde vid detta besök';
    $pickerDescription = 'Välj utrustning vi tog med just denna gång (visas på platssidan under besöksraden).';
    include dirname(__DIR__) . '/partials/product-picker.php';
    ?>

    <button type="submit" class="btn btn-primary btn--full" style="margin-top:var(--space-6);">Spara ändringar</button>
</form>
```

- [ ] **Step 6: Add product picker to visit-create view**

Open `views/visits/create.php`. Find the submit button (likely near the end of the form) and add the partial include right before it, with the same `$pickerTitle` / `$pickerDescription` as in step 5.

If the file structure differs, place the include INSIDE the `<form>` tag, after all other form groups, before the submit button.

- [ ] **Step 7: Smoke test**

```bash
./serve.sh
```

Test flow:
1. `/adm/platser/<slug>/besok/nytt` — verify product picker appears, select 2 products, save
2. Open the saved visit, click Redigera — verify the 2 products are pre-checked
3. Uncheck one, save, reopen edit — verify only 1 is checked
4. Confirm `place_products` (different system) for the parent place is unaffected

- [ ] **Step 8: Commit**

```bash
git add app/Controllers/VisitController.php views/visits/edit.php views/visits/create.php
git commit -m "Wire visit_products into visit create/edit"
```

---

## Task 7: Render visit products on public place page

**Files:**
- Modify: `app/Controllers/PublicController.php` — `placeDetail`
- Modify: `views/public/place-detail.php`

- [ ] **Step 1: Load per-visit products in PublicController**

Open `app/Controllers/PublicController.php`. Find `placeDetail` (around the area where `$placeProducts` is loaded — line 156). After:

```php
        $placeProducts = (new AmazonProduct($this->pdo))->getByPlaceId((int) $place['id']);
```

Add:

```php
        require_once dirname(__DIR__) . '/Models/VisitProduct.php';
        $visitIds      = array_map('intval', array_column($visits, 'id'));
        $visitProducts = (new VisitProduct($this->pdo))->findByVisitIds($visitIds);
```

(`$visits` is the published visits list already in scope.)

Update the `view(...)` call (around line 277) to include `$visitProducts`:

```php
        view('public/place-detail', compact('place', 'visits', 'images', 'tags', 'avgRating', 'pageTitle', 'seoMeta', 'schemas', 'faqItems', 'useLeaflet', 'placeProducts', 'visitProducts', 'visitImageCounts', 'previewImage'), 'public');
```

- [ ] **Step 2: Update place-detail view — rename heading**

Open `views/public/place-detail.php`. The existing `placeProducts` section (lines 157-202) currently has heading "Produkter vi använde här". Change the heading to clarify it's the place-level recommendations:

Find:

```php
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
            Produkter vi använde här
        </h2>
```

Replace with:

```php
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
            Rekommenderat för platsen
        </h2>
```

- [ ] **Step 3: Render per-visit products inside each visit card**

In `views/public/place-detail.php`, find the visit list loop (around lines 130-151). Inside the loop body, after the `<?php if ($v['approved_public_text']): ?> … <?php endif; ?>` block (around line 149), add:

```php
                            <?php
                            $vps = $visitProducts[(int) $v['id']] ?? [];
                            if (!empty($vps)):
                            ?>
                                <div class="pub-visit-card__products" style="margin-top:var(--space-2); display:flex; flex-wrap:wrap; gap:var(--space-2);">
                                    <?php foreach ($vps as $vp): ?>
                                        <span style="display:inline-flex; align-items:center; gap:var(--space-1); padding:2px 8px; border:1px solid var(--color-border); border-radius:var(--radius-sm); background:var(--color-bg); font-size:var(--text-xs); color:var(--color-text-muted);">
                                            <?= htmlspecialchars($vp['title']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
```

This renders a small chip row of products used at THAT specific visit, inside the visit card.

- [ ] **Step 4: Smoke test**

```bash
./serve.sh
```

Test scenarios:
1. Public place with `place_products` only, no published visits: only "Rekommenderat för platsen" section visible
2. Public place with `visit_products` on at least one published visit: visit card shows product chips
3. Public place with both: place-level section + per-visit chips
4. Non-public place (`public_allowed = 0`): page itself is hidden (no change in behavior)

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/PublicController.php views/public/place-detail.php
git commit -m "Render per-visit products on public place page"
```

---

## Task 8: Final verification

- [ ] **Step 1: Run all touched tests**

```bash
php tests/test_place_description_isolation.php
php tests/test_place_ai_inputs.php
php tests/test_visit_products.php
```

Expected: all PASS (or `SKIP` for the visit_products test if dev DB lacks fixtures).

- [ ] **Step 2: Run the full existing test suite**

```bash
for t in tests/test_*.php; do echo "--- $t ---"; php "$t" || echo "FAILED: $t"; done
```

Expected: no FAILED lines.

- [ ] **Step 3: Walkthrough in PWA**

Open the local server in mobile browser / PWA mode and verify the full flow:

1. Add a new place with a description, no visits — confirm public page shows description + "Rekommenderat" section if any place_products selected
2. Add a visit, attach 2 products (e.g. Petromax Atago, Cadac Safari Chef), publish — confirm public visit row shows those product chips
3. Add a second visit on the same place with a different product — confirm both visits show their own products
4. On the place edit page, click "Brodera ut text" — confirm a fresh draft is generated based on the published visits and current description, and `default_public_text` only updates when you click "Spara ändringar"
5. Approve a visit AI draft — confirm `places.default_public_text` is unchanged afterwards (compare via DB or admin UI)

- [ ] **Step 4: Final commit (if any cleanup left)**

If anything else needed touching:

```bash
git add -A
git commit -m "Wrap up place description / visit products work"
```

Otherwise skip.

- [ ] **Step 5: Push**

```bash
git push
```

---

## Summary of behavioral changes

- **Visit AI approval no longer overwrites `places.default_public_text`.** That field is now only mutated via the place edit form (manual save, with optional "Brodera ut text" populating the textarea first).
- **Place AI ("Brodera ut text") uses only published visit reviews + the place's current `default_public_text`** as input. Unpublished raw notes / ratings are ignored.
- **Visits can have their own product associations** (`visit_products`) separate from the place-level `place_products`. Both are picked via the same shared partial.
- **Public place page** displays:
  - "Rekommenderat för platsen" — from `place_products` (place-level curated picks)
  - Per-visit product chips inside each published visit's card — from `visit_products`
- **Schema:** new `visit_products` table; no changes to `places`, `visits`, `place_products`, or `ai_drafts`.
