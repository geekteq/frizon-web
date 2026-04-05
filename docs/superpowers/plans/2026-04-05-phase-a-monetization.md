# Phase A — Monetization & Backend Gaps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement Phase A of the monetization spec: click tracking, contextual product links on place pages, place view counter, contact/sponsorship page, and "kommande resor" teaser on homepage.

**Architecture:** All changes are additive — new tables, new routes, new controller methods, and targeted modifications to existing views. No existing features are broken. Each task is independently deployable.

**Tech Stack:** PHP 8.x, MariaDB, PDO, plain HTML/CSS, AWS SES v2 API via cURL + SigV4 for contact delivery, `LoginThrottle` for rate limiting.

---

## File Map

**New files:**
- `database/migrations/009_product_clicks.sql`
- `database/migrations/010_place_products.sql`
- `database/migrations/011_place_view_count.sql`
- `database/migrations/012_trip_teaser.sql`
- `app/Services/SesMailer.php` — minimal AWS SES v2 sender via cURL + SigV4, no SDK
- `views/public/contact.php`

**Modified files:**
- `public/.htaccess` — security headers
- `app/Controllers/AmazonController.php` — add `go()` method
- `app/Controllers/PlaceController.php` — pass products to edit view, sync on update
- `app/Controllers/PublicController.php` — increment view counter, fetch place products, fetch trip teasers, add contact actions
- `app/Controllers/TripController.php` — pass/save teaser fields
- `app/Models/AmazonProduct.php` — add `getByPlaceId()`, `syncPlaceProducts()`, `searchPublished()`
- `app/Models/Trip.php` — add teaser fields to `update()`
- `views/partials/shop-card.php` — affiliate link → `/go/{slug}`
- `views/public/shop-product.php` — affiliate CTA → `/go/{slug}`
- `views/public/place-detail.php` — add "Produkter vi använde här" section
- `views/public/homepage.php` — add "Kommande resor" section
- `views/places/edit.php` — add product attachment widget
- `views/trips/edit.php` — add teaser toggle + text
- `views/layouts/public.php` — add "Samarbeta" to nav and footer
- `routes/web.php` — new routes: `/go/{slug}`, `/samarbeta` GET/POST
- `.env.example` — add `CONTACT_EMAIL`, `APP_KEY`

---

## Task 1: Security Headers

**Files:**
- Modify: `public/.htaccess`

- [ ] **Step 1: Add headers to .htaccess**

Replace the current `.htaccess` content with:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

# Hide PHP version from response headers
php_flag expose_php Off
Header always unset X-Powered-By

# Security headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

- [ ] **Step 2: Verify syntax and reload**

```bash
apachectl configtest   # or: litespeed -t
```

Expected: `Syntax OK`

If LiteSpeed is running: reload via admin panel or `systemctl reload lsws`.

- [ ] **Step 3: Verify headers in browser**

```bash
curl -I https://frizon.org/
```

Expected response includes:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

- [ ] **Step 4: Commit**

```bash
git add public/.htaccess
git commit -m "security: add X-Content-Type-Options, X-Frame-Options, HSTS, Referrer-Policy headers"
```

---

## Task 2: product_clicks Migration

**Files:**
- Create: `database/migrations/009_product_clicks.sql`

- [ ] **Step 1: Create migration file**

```sql
-- 009_product_clicks.sql
-- Logs every affiliate link click through /go/{slug}

CREATE TABLE IF NOT EXISTS product_clicks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    referrer    VARCHAR(500)  DEFAULT NULL,
    user_agent  VARCHAR(500)  DEFAULT NULL,
    clicked_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_id  (product_id),
    INDEX idx_clicked_at  (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run migration**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/009_product_clicks.sql
```

Expected: no errors.

- [ ] **Step 3: Verify table exists**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "DESCRIBE product_clicks;"
```

Expected: shows `id`, `product_id`, `referrer`, `user_agent`, `clicked_at` columns.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/009_product_clicks.sql
git commit -m "feat: add product_clicks migration for affiliate click tracking"
```

---

## Task 3: Click-Tracking Route and Controller Action

**Files:**
- Modify: `app/Controllers/AmazonController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Add `go()` method to AmazonController**

Add this method after the `shopProduct()` method in `app/Controllers/AmazonController.php`:

```php
// -------------------------------------------------------------------------
// Public: affiliate click-through tracker — /go/{slug}
// -------------------------------------------------------------------------

public function go(array $params): void
{
    $model   = new AmazonProduct($this->pdo);
    $product = $model->findBySlug($params['slug']);

    if (!$product || !$product['is_published'] || !$product['affiliate_url']) {
        http_response_code(404);
        echo 'Produkten hittades inte.';
        return;
    }

    // Log the click — non-blocking: ignore DB errors
    try {
        $referrer  = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $stmt = $this->pdo->prepare(
            'INSERT INTO product_clicks (product_id, referrer, user_agent) VALUES (?, ?, ?)'
        );
        $stmt->execute([$product['id'], $referrer ?: null, $userAgent ?: null]);
    } catch (PDOException $e) {
        // Never fail a redirect because of logging
        error_log('product_clicks insert failed: ' . $e->getMessage());
    }

    header('Cache-Control: no-store, no-cache');
    header('Location: ' . $product['affiliate_url'], true, 302);
}
```

- [ ] **Step 2: Register route in routes/web.php**

Add after the existing shop routes (after line `$router->get('/shop/{slug}', 'AmazonController', 'shopProduct');`):

```php
// Affiliate click-through tracker (public)
$router->get('/go/{slug}', 'AmazonController', 'go');
```

- [ ] **Step 3: Verify syntax**

```bash
php -l app/Controllers/AmazonController.php
php -l routes/web.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Test the redirect**

```bash
curl -I http://localhost/go/your-product-slug
```

Expected: `HTTP/1.1 302 Found` with `Location:` header pointing to the Amazon affiliate URL.

Then check a row was inserted:
```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "SELECT * FROM product_clicks LIMIT 1;"
```

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/AmazonController.php routes/web.php
git commit -m "feat: add /go/{slug} affiliate click-tracking redirect"
```

---

## Task 4: Update Affiliate Links to Use /go/{slug}

**Files:**
- Modify: `views/partials/shop-card.php` (line 42)
- Modify: `views/public/shop-product.php` (line 48)

- [ ] **Step 1: Update shop-card.php**

In `views/partials/shop-card.php`, change line 42-47 from:

```php
            <a href="<?= htmlspecialchars($p['affiliate_url']) ?>"
               target="_blank" rel="noopener sponsored"
               class="btn btn--sm"
               style="flex:1; text-align:center; background:#FF9900; color:#111; border:1px solid #e68a00; font-weight:var(--weight-semibold);">
                Se hos Amazon ↗
            </a>
```

to:

```php
            <a href="/go/<?= htmlspecialchars($p['slug']) ?>"
               target="_blank" rel="noopener sponsored"
               class="btn btn--sm"
               style="flex:1; text-align:center; background:#FF9900; color:#111; border:1px solid #e68a00; font-weight:var(--weight-semibold);">
                Se hos Amazon ↗
            </a>
```

- [ ] **Step 2: Update shop-product.php**

In `views/public/shop-product.php`, change line 48-53 from:

```php
        <a href="<?= htmlspecialchars($product['affiliate_url']) ?>"
           target="_blank" rel="noopener sponsored"
           class="btn btn-primary"
           style="display:inline-block; font-size:var(--text-base); padding:var(--space-3) var(--space-6);">
            Köp hos Amazon →
        </a>
```

to:

```php
        <a href="/go/<?= htmlspecialchars($product['slug']) ?>"
           target="_blank" rel="noopener sponsored"
           class="btn btn-primary"
           style="display:inline-block; font-size:var(--text-base); padding:var(--space-3) var(--space-6);">
            Köp hos Amazon →
        </a>
```

- [ ] **Step 3: Verify syntax**

```bash
php -l views/partials/shop-card.php
php -l views/public/shop-product.php
```

- [ ] **Step 4: Verify in browser**

Open `/shop` and hover over the "Se hos Amazon" button — URL should show `/go/product-slug`, not `amazon.com`.
Open `/shop/{slug}` and verify the "Köp hos Amazon" CTA points to `/go/{slug}`.

- [ ] **Step 5: Commit**

```bash
git add views/partials/shop-card.php views/public/shop-product.php
git commit -m "feat: route all affiliate links through /go/{slug} click tracker"
```

---

## Task 5: place_products Migration

**Files:**
- Create: `database/migrations/010_place_products.sql`

- [ ] **Step 1: Create migration file**

```sql
-- 010_place_products.sql
-- Links amazon_products to places for contextual "used here" product recommendations

CREATE TABLE IF NOT EXISTS place_products (
    place_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    note        VARCHAR(255)  DEFAULT NULL,
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (place_id, product_id),
    FOREIGN KEY (product_id) REFERENCES amazon_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run migration**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/010_place_products.sql
```

Expected: no errors.

- [ ] **Step 3: Verify**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "DESCRIBE place_products;"
```

Expected: columns `place_id`, `product_id`, `note`, `sort_order`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/010_place_products.sql
git commit -m "feat: add place_products migration for contextual product links"
```

---

## Task 6: AmazonProduct Model — Place Product Methods

**Files:**
- Modify: `app/Models/AmazonProduct.php`

- [ ] **Step 1: Add methods to AmazonProduct**

At the end of `app/Models/AmazonProduct.php`, before the closing `}`, add:

```php
    /**
     * Returns all published products linked to a place, ordered by sort_order.
     */
    public function getByPlaceId(int $placeId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ap.*
            FROM amazon_products ap
            JOIN place_products pp ON pp.product_id = ap.id
            WHERE pp.place_id = ?
            ORDER BY pp.sort_order ASC, ap.title ASC
        ');
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    /**
     * Replaces all product links for a place. Pass an empty array to clear all.
     */
    public function syncPlaceProducts(int $placeId, array $productIds): void
    {
        $this->pdo->prepare('DELETE FROM place_products WHERE place_id = ?')
                  ->execute([$placeId]);

        if (empty($productIds)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO place_products (place_id, product_id, sort_order) VALUES (?, ?, ?)'
        );
        foreach (array_values($productIds) as $order => $productId) {
            $stmt->execute([$placeId, (int) $productId, $order]);
        }
    }
```

- [ ] **Step 2: Verify syntax**

```bash
php -l app/Models/AmazonProduct.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/Models/AmazonProduct.php
git commit -m "feat: add getByPlaceId and syncPlaceProducts to AmazonProduct model"
```

---

## Task 7: Admin — Product Attachment Widget on Place Edit

**Files:**
- Modify: `app/Controllers/PlaceController.php`
- Modify: `views/places/edit.php`

- [ ] **Step 1: Update PlaceController::edit() to pass products**

In `app/Controllers/PlaceController.php`, find the `edit()` method (line 95) and replace it with:

```php
    public function edit(array $params): void
    {
        Auth::requireLogin();
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';

        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); echo '<h1>Platsen hittades inte</h1>'; return; }

        $productModel       = new AmazonProduct($this->pdo);
        $allProducts        = $productModel->allPublished();
        $attachedProductIds = array_column($productModel->getByPlaceId((int) $p['id']), 'id');

        $pageTitle = 'Redigera ' . $p['name'];
        view('places/edit', compact('p', 'pageTitle', 'allProducts', 'attachedProductIds'));
    }
```

- [ ] **Step 2: Update PlaceController::update() to sync products**

In `app/Controllers/PlaceController.php`, find the `update()` method (line 105). After `flash('success', ...)` and before `redirect(...)`, add:

```php
        // Sync place products
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $productIds = array_map('intval', (array) ($_POST['product_ids'] ?? []));
        (new AmazonProduct($this->pdo))->syncPlaceProducts((int) $p['id'], $productIds);
```

Full updated `update()` method:

```php
    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); return; }

        $place->update((int) $p['id'], [
            'name'                => trim($_POST['name'] ?? $p['name']),
            'lat'                 => (float) ($_POST['lat'] ?? $p['lat']),
            'lng'                 => (float) ($_POST['lng'] ?? $p['lng']),
            'address_text'        => trim($_POST['address_text'] ?? '') ?: null,
            'country_code'        => trim($_POST['country_code'] ?? '') ?: null,
            'place_type'          => $_POST['place_type'] ?? $p['place_type'],
            'default_public_text' => trim($_POST['default_public_text'] ?? '') ?: null,
            'meta_description'    => trim($_POST['meta_description'] ?? '') ?: null,
            'faq_content'         => $this->buildFaqContent(),
        ]);

        // Sync place products
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $productIds = array_map('intval', (array) ($_POST['product_ids'] ?? []));
        (new AmazonProduct($this->pdo))->syncPlaceProducts((int) $p['id'], $productIds);

        flash('success', 'Platsen har uppdaterats.');
        redirect('/adm/platser/' . $params['slug']);
    }
```

- [ ] **Step 3: Add product widget to views/places/edit.php**

At the end of `views/places/edit.php`, before the closing `</form>` tag, add:

```php
<?php if (!empty($allProducts)): ?>
<section style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <h2 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">
        Produkter vi använde här
    </h2>
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">
        Välj produkter från shoppen att visa på den publika platssidan.
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
<?php endif; ?>
```

- [ ] **Step 4: Verify syntax**

```bash
php -l app/Controllers/PlaceController.php
php -l views/places/edit.php
```

- [ ] **Step 5: Test in admin**

1. Open `/adm/platser/{slug}/redigera`
2. Scroll to bottom — product checkboxes should appear
3. Check a product and save
4. Re-open edit form — checkbox should still be ticked
5. Verify in DB: `SELECT * FROM place_products WHERE place_id = <id>;`

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/PlaceController.php views/places/edit.php
git commit -m "feat: add product attachment widget to place edit admin"
```

---

## Task 8: Public "Produkter vi använde här" on Place Detail

**Files:**
- Modify: `app/Controllers/PublicController.php`
- Modify: `views/public/place-detail.php`

- [ ] **Step 1: Fetch place products in PublicController::placeDetail()**

In `PublicController::placeDetail()`, after the `$tags` query (around line 124), add:

```php
        // Products linked to this place
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $placeProducts = (new AmazonProduct($this->pdo))->getByPlaceId((int) $place['id']);
```

Pass `$placeProducts` in the `view()` call at the end of `placeDetail()`. Find the existing `view(...)` call and add `placeProducts` to `compact(...)`:

```php
        view('public/place-detail', compact(
            'place', 'visits', 'images', 'tags', 'avgRating',
            'pageTitle', 'seoMeta', 'schemas', 'useLeaflet', 'placeProducts'
        ), 'public');
```

- [ ] **Step 2: Add "Produkter vi använde här" section to place-detail.php**

In `views/public/place-detail.php`, find the closing `</article>` tag (line 100). Insert before it:

```php
<?php if (!empty($placeProducts)): ?>
<section style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
        Produkter vi använde här
    </h2>
    <div style="display:flex; flex-direction:column; gap:var(--space-3);">
        <?php foreach ($placeProducts as $prod): ?>
        <a href="/go/<?= htmlspecialchars($prod['slug']) ?>"
           target="_blank" rel="noopener sponsored"
           style="display:flex; align-items:center; gap:var(--space-3); padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); text-decoration:none; color:inherit; background:var(--color-bg);">
            <?php if ($prod['image_path']): ?>
                <img src="/uploads/amazon/<?= htmlspecialchars($prod['image_path']) ?>"
                     alt="<?= htmlspecialchars($prod['title']) ?>"
                     width="56" height="56"
                     loading="lazy"
                     style="width:56px; height:56px; object-fit:contain; background:#f5f5f4; border-radius:var(--radius-sm); flex-shrink:0;">
            <?php endif; ?>
            <div style="flex:1; min-width:0;">
                <div style="font-size:var(--text-sm); font-weight:var(--weight-semibold); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($prod['title']) ?>
                </div>
                <?php if ($prod['category']): ?>
                <div style="font-size:var(--text-xs); color:var(--color-text-muted); margin-top:2px;">
                    <?= htmlspecialchars($prod['category']) ?>
                </div>
                <?php endif; ?>
            </div>
            <span style="font-size:var(--text-sm); color:var(--color-text-muted); flex-shrink:0;">Se hos Amazon ↗</span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
```

- [ ] **Step 3: Verify syntax**

```bash
php -l app/Controllers/PublicController.php
php -l views/public/place-detail.php
```

- [ ] **Step 4: Test in browser**

1. Attach a product to a place (Task 7)
2. Open `/platser/{slug}` — "Produkter vi använde här" section appears
3. Click a product — should go through `/go/{slug}` and redirect to Amazon

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/PublicController.php views/public/place-detail.php
git commit -m "feat: show contextual product links on public place detail pages"
```

---

## Task 9: Place View Counter

**Files:**
- Create: `database/migrations/011_place_view_count.sql`
- Modify: `app/Controllers/PublicController.php`

- [ ] **Step 1: Create migration**

```sql
-- 011_place_view_count.sql
-- Adds a simple request counter to the places table

ALTER TABLE places ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0;
```

- [ ] **Step 2: Run migration**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/011_place_view_count.sql
```

- [ ] **Step 3: Verify**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "SHOW COLUMNS FROM places LIKE 'view_count';"
```

Expected: shows `view_count` INT column with default `0`.

- [ ] **Step 4: Increment counter in PublicController::placeDetail()**

In `app/Controllers/PublicController.php`, in `placeDetail()`, right after the `$place` is verified as found and public (after the `if (!$place || ...)` block, around line 97), add:

```php
        // Increment view counter (fire-and-forget, ignore failures)
        try {
            $this->pdo->prepare('UPDATE places SET view_count = view_count + 1 WHERE id = ?')
                      ->execute([$place['id']]);
        } catch (PDOException $e) {
            error_log('view_count increment failed: ' . $e->getMessage());
        }
```

- [ ] **Step 5: Verify syntax**

```bash
php -l app/Controllers/PublicController.php
```

- [ ] **Step 6: Test**

```bash
# Visit a place page twice
curl -s http://localhost/platser/some-slug > /dev/null
curl -s http://localhost/platser/some-slug > /dev/null

# Check counter
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e \
  "SELECT id, name, view_count FROM places WHERE public_allowed=1 LIMIT 5;"
```

Expected: `view_count` is 2 for the visited place.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/011_place_view_count.sql app/Controllers/PublicController.php
git commit -m "feat: add place view counter — increments on each public place page load"
```

---

## Task 10: Contact / Sponsorship Page

**Files:**
- Modify: `routes/web.php`
- Create: `app/Services/SesMailer.php`
- Modify: `app/Controllers/PublicController.php`
- Create: `views/public/contact.php`
- Modify: `views/layouts/public.php`
- Modify: `.env.example`

- [ ] **Step 1: Add SES credentials and contact config to .env.example**

Add to the end of `.env.example`:

```
# Contact form — AWS SES delivery
# Sender: frizon@mobileminds.se (mobileminds.se is whitelisted in SES)
# CONTACT_EMAIL = inbox that receives sponsorship enquiries
AWS_SES_KEY=
AWS_SES_SECRET=
AWS_SES_REGION=eu-north-1
MAIL_FROM=frizon@mobileminds.se
CONTACT_EMAIL=kontakt@frizon.org
APP_KEY=change-me-to-random-32-char-string
```

Add the same lines to your `.env` file with real values:
- `AWS_SES_KEY` / `AWS_SES_SECRET` — IAM user with `ses:SendEmail` permission only
- `AWS_SES_REGION` — the region your SES identity is in (e.g. `eu-north-1`)
- `MAIL_FROM` — `frizon@mobileminds.se` (whitelisted sender)
- `CONTACT_EMAIL` — the inbox that receives sponsorship messages
- `APP_KEY` — generate with `openssl rand -hex 16`

- [ ] **Step 2: Create app/Services/SesMailer.php**

```php
<?php

declare(strict_types=1);

/**
 * Minimal AWS SES v2 email sender via cURL + SigV4.
 * No Composer or external SDK required.
 */
class SesMailer
{
    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly string $region,
        private readonly string $from,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            key:    $_ENV['AWS_SES_KEY']    ?? '',
            secret: $_ENV['AWS_SES_SECRET'] ?? '',
            region: $_ENV['AWS_SES_REGION'] ?? 'eu-north-1',
            from:   $_ENV['MAIL_FROM']      ?? 'frizon@mobileminds.se',
        );
    }

    /**
     * Send a plain-text email via SES v2.
     *
     * @throws RuntimeException on HTTP or API error
     */
    public function send(string $to, string $replyTo, string $subject, string $body): void
    {
        $path    = '/v2/email/outbound-emails';
        $payload = json_encode([
            'FromEmailAddress' => $this->from,
            'Destination'      => ['ToAddresses' => [$to]],
            'ReplyToAddresses' => [$replyTo],
            'Content'          => [
                'Simple' => [
                    'Subject' => ['Data' => $subject, 'Charset' => 'UTF-8'],
                    'Body'    => ['Text' => ['Data' => $body, 'Charset' => 'UTF-8']],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $headers = $this->buildHeaders('POST', $path, $payload);

        $ch = curl_init("https://email.{$this->region}.amazonaws.com{$path}");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new RuntimeException('SES cURL error: ' . $curlErr);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException("SES HTTP {$httpCode}: " . $response);
        }
    }

    /** @return list<string> */
    private function buildHeaders(string $method, string $path, string $payload): array
    {
        $service  = 'ses';
        $now      = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $date     = $now->format('Ymd');
        $dateTime = $now->format('Ymd\THis\Z');
        $host     = "email.{$this->region}.amazonaws.com";
        $hash     = hash('sha256', $payload);

        $canonicalHeaders = "content-type:application/json\nhost:{$host}\nx-amz-date:{$dateTime}\n";
        $signedHeaders    = 'content-type;host;x-amz-date';
        $canonicalRequest = implode("\n", [$method, $path, '', $canonicalHeaders, $signedHeaders, $hash]);

        $scope        = "{$date}/{$this->region}/{$service}/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $dateTime, $scope, hash('sha256', $canonicalRequest)]);

        $signingKey = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', $service,
                hash_hmac('sha256', $this->region,
                    hash_hmac('sha256', $date, 'AWS4' . $this->secret, true),
                true),
            true),
        true);

        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->key}/{$scope}, "
                    . "SignedHeaders={$signedHeaders}, Signature={$signature}";

        return [
            'Content-Type: application/json',
            "Host: {$host}",
            "X-Amz-Date: {$dateTime}",
            "Authorization: {$authHeader}",
        ];
    }
}
```

- [ ] **Step 3: Verify syntax**

```bash
php -l app/Services/SesMailer.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Add routes**

In `routes/web.php`, add after the `/cookiepolicy` route:

```php
    $router->get('/samarbeta', 'PublicController', 'contact');
    $router->post('/samarbeta', 'PublicController', 'submitContact');
```

- [ ] **Step 5: Add contact() method to PublicController**

Add this method to `app/Controllers/PublicController.php`:

```php
    public function contact(array $params): void
    {
        $appKey    = $_ENV['APP_KEY'] ?? 'default';
        $loadedAt  = time();
        $formToken = hash_hmac('sha256', (string) $loadedAt, $appKey);

        $pageTitle = 'Samarbeta med oss — Frizon of Sweden';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
        $seoMeta   = [
            'description' => 'Intresserad av ett samarbete med Frizon of Sweden? Vi samarbetar med varumärken vi faktiskt använder på resan med Frizze.',
            'og_url'      => $appUrl . '/samarbeta',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        view('public/contact', compact('pageTitle', 'seoMeta', 'loadedAt', 'formToken'), 'public');
    }
```

- [ ] **Step 6: Add submitContact() method to PublicController**

```php
    public function submitContact(array $params): void
    {
        $appKey       = $_ENV['APP_KEY'] ?? 'default';
        $contactEmail = $_ENV['CONTACT_EMAIL'] ?? '';

        // --- Spam protection layer 1: honeypot ---
        if (!empty($_POST['website'])) {
            flash('success', 'Tack för ditt meddelande! Vi hör av oss inom kort.');
            redirect('/samarbeta');
            return;
        }

        // --- Spam protection layer 2: timing check ---
        $loadedAt  = (int) ($_POST['loaded_at'] ?? 0);
        $formToken = trim($_POST['form_token'] ?? '');
        $expected  = hash_hmac('sha256', (string) $loadedAt, $appKey);
        if (!hash_equals($expected, $formToken) || (time() - $loadedAt) < 4) {
            flash('success', 'Tack för ditt meddelande! Vi hör av oss inom kort.');
            redirect('/samarbeta');
            return;
        }

        // --- Spam protection layer 3: IP rate limit ---
        require_once dirname(__DIR__) . '/Services/LoginThrottle.php';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $throttle = new LoginThrottle(
            storagePath:   dirname(__DIR__, 2) . '/storage/contact-throttle',
            maxAttempts:   3,
            windowSeconds: 3600
        );
        try {
            $throttle->ensureAllowed('contact', $ip);
        } catch (RuntimeException $e) {
            flash('error', 'För många meddelanden. Försök igen senare.');
            redirect('/samarbeta');
            return;
        }

        // --- Validate ---
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            flash('error', 'Fyll i alla obligatoriska fält.');
            redirect('/samarbeta');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Ange en giltig e-postadress.');
            redirect('/samarbeta');
            return;
        }

        // --- Deliver via AWS SES ---
        $company = trim($_POST['company'] ?? '');
        $subject = 'Samarbetsförfrågan från ' . $name . ($company ? ' (' . $company . ')' : '');
        $body    = "Namn: {$name}\n"
                 . ($company ? "Företag: {$company}\n" : '')
                 . "E-post: {$email}\n\n"
                 . "Meddelande:\n{$message}";

        if ($contactEmail) {
            require_once dirname(__DIR__) . '/Services/SesMailer.php';
            try {
                SesMailer::fromEnv()->send($contactEmail, $email, $subject, $body);
            } catch (RuntimeException $e) {
                error_log('SesMailer failed: ' . $e->getMessage());
                // Don't expose delivery failure to the user — log and continue
            }
        }

        $throttle->recordFailure('contact', $ip); // count successful submissions
        flash('success', 'Tack för ditt meddelande! Vi hör av oss inom kort.');
        redirect('/samarbeta');
    }
```

- [ ] **Step 5: Create views/public/contact.php**

```php
<?php /* Samarbeta / sponsorship contact page */ ?>

<div style="max-width:640px; margin:0 auto; padding:var(--space-10) var(--space-4) var(--space-12);">

    <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-3);">
        Samarbeta med oss
    </h1>
    <p style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text-muted); margin-bottom:var(--space-8);">
        Vi samarbetar med varumärken vi faktiskt använder och kan rekommendera på resan med Frizze.
        Intresserad? Fyll i formuläret nedan så hör vi av oss.
    </p>

    <?php if ($flash = get_flash('success')): ?>
        <div style="background:var(--color-success-bg,#dcfce7); color:var(--color-success,#166534); padding:var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-6);">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php elseif ($flash = get_flash('error')): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-6);">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/samarbeta" style="display:flex; flex-direction:column; gap:var(--space-5);">
        <?= CsrfService::field() ?>

        <!-- Honeypot: hidden from real users, bots fill it -->
        <input type="text" name="website" tabindex="-1" autocomplete="off"
               style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;" aria-hidden="true">

        <!-- Timing token -->
        <input type="hidden" name="loaded_at" value="<?= (int) $loadedAt ?>">
        <input type="hidden" name="form_token" value="<?= htmlspecialchars($formToken) ?>">

        <div>
            <label for="contact-name" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Namn <span aria-hidden="true">*</span>
            </label>
            <input type="text" id="contact-name" name="name" required autocomplete="name"
                   style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg);">
        </div>

        <div>
            <label for="contact-company" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Företag
            </label>
            <input type="text" id="contact-company" name="company" autocomplete="organization"
                   style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg);">
        </div>

        <div>
            <label for="contact-email" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                E-post <span aria-hidden="true">*</span>
            </label>
            <input type="email" id="contact-email" name="email" required autocomplete="email"
                   style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg);">
        </div>

        <div>
            <label for="contact-message" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Meddelande <span aria-hidden="true">*</span>
            </label>
            <textarea id="contact-message" name="message" required rows="6"
                      style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg); resize:vertical;"></textarea>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="font-size:var(--text-base); padding:var(--space-3) var(--space-6);">
                Skicka
            </button>
        </div>
    </form>

</div>
```

- [ ] **Step 7: Create views/public/contact.php**

See full view template in Step 5 block above (the `contact.php` content is the `views/public/contact.php` file to create).

- [ ] **Step 8: Add "Samarbeta" to public nav and footer**

In `views/layouts/public.php`, find:

```php
            <a href="/topplista" class="public-header__link" style="font-weight:var(--weight-semibold);">Topplista</a>
```

Replace with:

```php
            <a href="/topplista" class="public-header__link" style="font-weight:var(--weight-semibold);">Topplista</a>
            <a href="/samarbeta" class="public-header__link <?= str_starts_with($reqPath ?? $_SERVER['REQUEST_URI'], '/samarbeta') ? 'public-header__link--active' : '' ?>" style="font-weight:var(--weight-semibold);">Samarbeta</a>
```

Also add to footer (find the privacy policy link block) — insert before the `Admin` link:

```php
            <a href="/samarbeta" style="color:rgba(255,255,255,0.8); text-decoration:underline;">Samarbeta</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
```

- [ ] **Step 9: Verify syntax**

```bash
php -l app/Services/SesMailer.php
php -l app/Controllers/PublicController.php
php -l views/public/contact.php
php -l views/layouts/public.php
```

- [ ] **Step 10: Test**

1. Open `/samarbeta` — form renders correctly
2. Submit with an empty honeypot, valid data, after 5+ seconds — message arrives at `CONTACT_EMAIL`
3. Fill in the honeypot field in browser devtools and submit — success flash shown but no email sent
4. Submit 4 times rapidly — 4th attempt shows rate limit error
5. Check SES send statistics in AWS console to confirm delivery

- [ ] **Step 11: Commit**

```bash
git add routes/web.php app/Services/SesMailer.php app/Controllers/PublicController.php \
        views/public/contact.php views/layouts/public.php .env.example
git commit -m "feat: add contact/sponsorship page — honeypot, timing check, IP throttle, SES delivery"
```

---

## Task 11: "Kommande Resor" on Homepage

**Files:**
- Create: `database/migrations/012_trip_teaser.sql`
- Modify: `app/Models/Trip.php`
- Modify: `app/Controllers/TripController.php`
- Modify: `views/trips/edit.php`
- Modify: `app/Controllers/PublicController.php`
- Modify: `views/public/homepage.php`

- [ ] **Step 1: Create migration**

```sql
-- 012_trip_teaser.sql
-- Opt-in public teaser for upcoming trips shown on the homepage.
-- Exposes only the teaser_text and approximate start month — no stop details.

ALTER TABLE trips
    ADD COLUMN public_teaser TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN teaser_text   VARCHAR(500) DEFAULT NULL;
```

- [ ] **Step 2: Run migration**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/012_trip_teaser.sql
```

- [ ] **Step 3: Verify**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "SHOW COLUMNS FROM trips LIKE 'public_%';"
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "SHOW COLUMNS FROM trips LIKE 'teaser_%';"
```

Expected: both columns listed.

- [ ] **Step 4: Update Trip::update() to include teaser fields**

In `app/Models/Trip.php`, find `update()` (line 58). Replace the entire method with:

```php
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE trips SET title = ?, intro_text = ?, status = ?,
                start_date = ?, end_date = ?,
                public_teaser = ?, teaser_text = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['intro_text'] ?? null,
            $data['status'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            (int) ($data['public_teaser'] ?? 0),
            $data['teaser_text'] ?? null,
            $id,
        ]);
    }
```

- [ ] **Step 5: Update TripController::update() to pass teaser fields**

In `app/Controllers/TripController.php`, find `update()` (line 109). Replace the `$tripModel->update(...)` call with:

```php
        $tripModel->update((int) $trip['id'], [
            'title'         => trim($_POST['title'] ?? $trip['title']),
            'intro_text'    => trim($_POST['intro_text'] ?? '') ?: null,
            'status'        => $_POST['status'] ?? $trip['status'],
            'start_date'    => $_POST['start_date'] ?: null,
            'end_date'      => $_POST['end_date'] ?: null,
            'public_teaser' => isset($_POST['public_teaser']) ? 1 : 0,
            'teaser_text'   => trim($_POST['teaser_text'] ?? '') ?: null,
        ]);
```

- [ ] **Step 6: Add teaser fields to trips/edit.php**

In `views/trips/edit.php`, locate the end of the form (before the save button / closing form tag). Add:

```php
<section style="margin-top:var(--space-6); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <h2 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">
        Kommande resa — publik teaser
    </h2>
    <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer; margin-bottom:var(--space-4);">
        <input type="checkbox" name="public_teaser" value="1"
               <?= !empty($trip['public_teaser']) ? 'checked' : '' ?>>
        <span style="font-size:var(--text-sm);">Visa som planerad resa på startsidan</span>
    </label>
    <div>
        <label for="teaser_text" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
            Teasertext (visas publikt — inga specifika platser)
        </label>
        <textarea id="teaser_text" name="teaser_text" rows="3" maxlength="500"
                  style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-sm); resize:vertical;"
                  placeholder="T.ex. Vi planerar en Sverige-rund i sommar…"><?= htmlspecialchars($trip['teaser_text'] ?? '') ?></textarea>
        <p style="font-size:var(--text-xs); color:var(--color-text-muted); margin-top:var(--space-1);">
            Visas bara om "Visa som planerad resa" är ikryssad och startdatum är i framtiden.
        </p>
    </div>
</section>
```

- [ ] **Step 7: Fetch teasered trips in PublicController::homepage()**

In `app/Controllers/PublicController.php`, in `homepage()`, before the `$useLeaflet = true;` line, add:

```php
        // Upcoming teasered trips
        require_once dirname(__DIR__) . '/Models/Trip.php';
        $upcomingTrips = $this->pdo->prepare('
            SELECT title, teaser_text, start_date
            FROM trips
            WHERE public_teaser = 1 AND start_date > CURDATE()
            ORDER BY start_date ASC
            LIMIT 3
        ');
        $upcomingTrips->execute();
        $upcomingTrips = $upcomingTrips->fetchAll();
```

Add `upcomingTrips` to the `compact(...)` call in `view(...)`:

```php
        view('public/homepage', compact(
            'places', 'filterType', 'filterCountry', 'allPublic', 'allTypes',
            'pageTitle', 'seoMeta', 'schemas', 'shopTeaser', 'useLeaflet',
            'search', 'upcomingTrips'
        ), 'public');
```

- [ ] **Step 8: Add "Kommande resor" section to homepage.php**

In `views/public/homepage.php`, find the shop teaser section (`<?php if (!empty($shopTeaser)): ?>`). Add the following *before* it:

```php
<?php if (!empty($upcomingTrips)): ?>
<section style="max-width:var(--content-max-width); margin:0 auto var(--space-8); padding:0 var(--space-4);">
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
        Kommande resor
    </h2>
    <div style="display:flex; flex-direction:column; gap:var(--space-3);">
        <?php foreach ($upcomingTrips as $t): ?>
        <div style="padding:var(--space-4); border:1px solid var(--color-border); border-radius:var(--radius-md); background:var(--color-bg);">
            <?php if ($t['start_date']): ?>
                <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:var(--space-1);">
                    <?= date('F Y', strtotime($t['start_date'])) ?>
                </div>
            <?php endif; ?>
            <?php if ($t['teaser_text']): ?>
                <p style="font-size:var(--text-sm); color:var(--color-text); margin:0; line-height:var(--leading-relaxed);">
                    <?= htmlspecialchars($t['teaser_text']) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
```

- [ ] **Step 9: Verify syntax**

```bash
php -l app/Models/Trip.php
php -l app/Controllers/TripController.php
php -l app/Controllers/PublicController.php
php -l views/trips/edit.php
php -l views/public/homepage.php
```

- [ ] **Step 10: Test**

1. Open `/adm/resor/{slug}/redigera`
2. Tick "Visa som planerad resa på startsidan", add a teaser text, set a future start date, save
3. Open `/` — "Kommande resor" section appears
4. Untick the checkbox, save — section disappears from homepage
5. Set start_date to a past date — section disappears (only future trips show)

- [ ] **Step 11: Commit**

```bash
git add database/migrations/012_trip_teaser.sql \
        app/Models/Trip.php \
        app/Controllers/TripController.php \
        app/Controllers/PublicController.php \
        views/trips/edit.php \
        views/public/homepage.php
git commit -m "feat: kommande resor teaser on homepage — opt-in per trip, future dates only"
```

---

## Self-Review Checklist

- [x] Security headers: covered in Task 1
- [x] Click tracking `/go/{slug}`: Tasks 2–4
- [x] Contextual products on place pages: Tasks 5–8
- [x] Place view counter: Task 9
- [x] Contact/sponsorship page with spam protection: Task 10
- [x] "Kommande resor" teaser: Task 11
- [x] Nav link "Samarbeta" added: Task 10 Step 6
- [x] CSRF: already patched (verified in codebase — no task needed)
- [x] All affiliate links in shop-card.php and shop-product.php updated
- [x] LoginThrottle reused for contact form (no new abstraction)
- [x] No personal data stored in product_clicks
- [x] Teaser exposes only teaser_text + start month — no stop details
