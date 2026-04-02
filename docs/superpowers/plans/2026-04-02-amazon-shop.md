# Amazon Shop Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a curated Amazon affiliate product shop at `/shop` with admin CRUD, AI-powered descriptions and SEO, auto-fetched product images, and a homepage teaser.

**Architecture:** New `AmazonController` handles both public (`/shop`, `/shop/{slug}`) and admin (`/adm/amazon-lista/*`) routes. `AmazonProduct` model follows the existing `Place` pattern. `AmazonFetcher` service handles cURL scraping of Amazon og:image/og:description and affiliate URL generation. `AiService` is extended with three new methods (shop description, shop SEO, Swedish translation). All auto-fetching and AI generation happens at save time.

**Tech Stack:** PHP 8.x, MariaDB, PDO, cURL, existing AiService/ClaudeAiProvider (Anthropic API), existing `layouts/public.php` and `layouts/app.php`.

---

## File Map

### New files
- `database/migrations/007_amazon_products.sql` — table definition
- `app/Models/AmazonProduct.php` — CRUD model
- `app/Services/AmazonFetcher.php` — cURL og:image fetch + affiliate URL generation
- `app/Controllers/AmazonController.php` — all routes (public + admin + AI)
- `views/amazon/index.php` — admin product list
- `views/amazon/create.php` — admin create form
- `views/amazon/edit.php` — admin edit form
- `views/public/shop.php` — public shop listing page
- `views/public/shop-product.php` — public product detail page
- `tests/test_amazon_shop.php` — unit tests for AmazonFetcher logic

### Modified files
- `app/Services/AiService.php` — add `generateShopDescription()`, `generateShopSeo()`, `translateToSwedish()` to interface + both providers
- `app/Controllers/PublicController.php` — update `sitemap()` to include shop URLs
- `views/layouts/public.php` — add "Shop" nav link
- `views/partials/nav-desktop.php` — add "Shop" in sidebar under "Publikt"
- `views/public/homepage.php` — add "Nytt i shoppen" teaser section
- `public/index.php` — extend image serving to include `/uploads/amazon/` path
- `routes/web.php` — register all new routes
- `.env.example` — add `AMAZON_ASSOCIATE_ID`
- `public/css/pages/public.css` — add shop-specific styles

---

## Task 1: Database migration and env config

**Files:**
- Create: `database/migrations/007_amazon_products.sql`
- Modify: `.env.example`

- [ ] **Step 1: Write the migration file**

```sql
-- 007_amazon_products.sql
CREATE TABLE amazon_products (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug               VARCHAR(255) NOT NULL UNIQUE,
    title              VARCHAR(255) NOT NULL,
    amazon_url         VARCHAR(2048) NOT NULL,
    affiliate_url      VARCHAR(2048) NOT NULL,
    image_path         VARCHAR(512) NULL,
    amazon_description TEXT NULL,
    our_description    TEXT NULL,
    seo_title          VARCHAR(255) NULL,
    seo_description    VARCHAR(320) NULL,
    category           VARCHAR(100) NULL,
    sort_order         SMALLINT UNSIGNED DEFAULT 0,
    is_featured        TINYINT(1) NOT NULL DEFAULT 0,
    is_published       TINYINT(1) NOT NULL DEFAULT 0,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run the migration**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/007_amazon_products.sql
```

Expected: no output (success). If table already exists, you'll see an error — check first with `SHOW TABLES LIKE 'amazon_products'`.

- [ ] **Step 3: Add AMAZON_ASSOCIATE_ID to .env.example**

Add after the `GA_MEASUREMENT_ID` line:

```
# Amazon Associates affiliate tag — used to build affiliate links
AMAZON_ASSOCIATE_ID=
```

Also add to `.env` (local dev value can be blank or a test tag):
```
AMAZON_ASSOCIATE_ID=frizon-test-21
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/007_amazon_products.sql .env.example
git commit -m "feat: add amazon_products migration and env config"
```

---

## Task 2: AmazonProduct model

**Files:**
- Create: `app/Models/AmazonProduct.php`

- [ ] **Step 1: Write the model**

```php
<?php

declare(strict_types=1);

class AmazonProduct
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** All products, optionally filtered by category or published status. */
    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM amazon_products';
        $where = [];
        $params = [];

        if (isset($filters['is_published'])) {
            $where[] = 'is_published = ?';
            $params[] = (int) $filters['is_published'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'title LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Featured first, then sort_order, then newest
        $sql .= ' ORDER BY is_featured DESC, sort_order ASC, updated_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allPublished(): array
    {
        return $this->all(['is_published' => 1]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM amazon_products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM amazon_products WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO amazon_products
                (slug, title, amazon_url, affiliate_url, image_path, amazon_description,
                 our_description, seo_title, seo_description, category, sort_order,
                 is_featured, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['amazon_url'],
            $data['affiliate_url'],
            $data['image_path'] ?? null,
            $data['amazon_description'] ?? null,
            $data['our_description'] ?? null,
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['category'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_featured'] ?? 0),
            (int) ($data['is_published'] ?? 0),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE amazon_products SET
                title = ?, amazon_url = ?, affiliate_url = ?, image_path = ?,
                amazon_description = ?, our_description = ?, seo_title = ?,
                seo_description = ?, category = ?, sort_order = ?,
                is_featured = ?, is_published = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['amazon_url'],
            $data['affiliate_url'],
            $data['image_path'] ?? null,
            $data['amazon_description'] ?? null,
            $data['our_description'] ?? null,
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['category'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_featured'] ?? 0),
            (int) ($data['is_published'] ?? 0),
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM amazon_products WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Latest N published products, for homepage teaser. */
    public function latestPublished(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM amazon_products
            WHERE is_published = 1
            ORDER BY created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /** All distinct categories used by published products. */
    public function publishedCategories(): array
    {
        $stmt = $this->pdo->query('
            SELECT DISTINCT category FROM amazon_products
            WHERE is_published = 1 AND category IS NOT NULL
            ORDER BY category ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** All distinct categories (including unpublished, for admin autocomplete). */
    public function allCategories(): array
    {
        $stmt = $this->pdo->query('
            SELECT DISTINCT category FROM amazon_products
            WHERE category IS NOT NULL
            ORDER BY category ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function generateSlug(string $title): string
    {
        $slug = mb_strtolower($title);
        $slug = strtr($slug, [
            'å' => 'a', 'ä' => 'a', 'ö' => 'o',
            'é' => 'e', 'è' => 'e', 'ü' => 'u',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/AmazonProduct.php
git commit -m "feat: add AmazonProduct model"
```

---

## Task 3: AmazonFetcher service

**Files:**
- Create: `app/Services/AmazonFetcher.php`
- Create: `tests/test_amazon_shop.php`

- [ ] **Step 1: Write the service**

```php
<?php

declare(strict_types=1);

class AmazonFetcher
{
    private string $associateId;
    private string $uploadDir;

    public function __construct(string $associateId, string $uploadDir)
    {
        $this->associateId = $associateId;
        $this->uploadDir   = rtrim($uploadDir, '/');
    }

    /**
     * Build an affiliate URL by injecting the associate tag into an Amazon URL.
     * Replaces any existing tag= parameter, or appends it.
     */
    public function buildAffiliateUrl(string $amazonUrl): string
    {
        $parsed = parse_url($amazonUrl);
        if (!$parsed) {
            return $amazonUrl;
        }

        parse_str($parsed['query'] ?? '', $params);
        $params['tag'] = $this->associateId;

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '')
              . ($parsed['path'] ?? '');

        return $base . '?' . http_build_query($params);
    }

    /**
     * Validate that a URL is on an Amazon domain.
     */
    public function isAmazonUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return (bool) preg_match('/(?:^|\.)amazon\.[a-z]{2,3}$/i', $host);
    }

    /**
     * Fetch og:image and og:description from an Amazon product page.
     * Returns ['image_url' => string|null, 'description' => string|null].
     */
    public function fetchProductMeta(string $amazonUrl): array
    {
        $ch = curl_init($amazonUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept-Language: sv-SE,sv;q=0.9',
                'Accept: text/html,application/xhtml+xml',
            ],
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            return ['image_url' => null, 'description' => null];
        }

        return [
            'image_url'   => $this->extractOgTag($html, 'og:image'),
            'description' => $this->extractOgTag($html, 'og:description'),
        ];
    }

    /**
     * Download an image from a URL and save it to the upload directory.
     * Returns the saved filename, or null on failure.
     */
    public function downloadImage(string $imageUrl): ?string
    {
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);

        $imageData = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$imageData || $httpCode !== 200) {
            return null;
        }

        // Detect extension from content
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($imageData);
        $ext   = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };

        if (!$ext) {
            return null;
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $path     = $this->uploadDir . '/' . $filename;
        file_put_contents($path, $imageData);

        return $filename;
    }

    private function extractOgTag(string $html, string $property): ?string
    {
        if (preg_match(
            '/<meta[^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\'](.*?)["\'][^>]*>/si',
            $html,
            $m
        )) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: null;
        }

        // Also try reversed attribute order: content first, then property
        if (preg_match(
            '/<meta[^>]+content=["\'](.*?)["\'][^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]*>/si',
            $html,
            $m
        )) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: null;
        }

        return null;
    }
}
```

- [ ] **Step 2: Write the test file**

```php
<?php
/**
 * Test: AmazonFetcher utility logic (no network calls).
 * Run: php tests/test_amazon_shop.php
 */

require_once dirname(__DIR__) . '/app/Services/AmazonFetcher.php';

$fetcher = new AmazonFetcher('frizonse-21', '/tmp/test-uploads');

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $got = ''): void
{
    global $passed, $failed;
    if ($ok) {
        printf("PASS: %s\n", $name);
        $passed++;
    } else {
        printf("FAIL: %s%s\n", $name, $got ? " — got: $got" : '');
        $failed++;
    }
}

// --- isAmazonUrl ---
check('amazon.se is valid',     $fetcher->isAmazonUrl('https://www.amazon.se/dp/B08N5WRWNW'));
check('amazon.com is valid',    $fetcher->isAmazonUrl('https://amazon.com/dp/B00TEST'));
check('amazon.co.uk is valid',  $fetcher->isAmazonUrl('https://www.amazon.co.uk/dp/B00TEST'));
check('evil.com not valid',     !$fetcher->isAmazonUrl('https://evil.com/amazon.se'));
check('notamazon.se not valid', !$fetcher->isAmazonUrl('https://notamazon.se/product'));

// --- buildAffiliateUrl: appends tag ---
$url1 = $fetcher->buildAffiliateUrl('https://www.amazon.se/dp/B08N5WRWNW');
check('tag appended',           str_contains($url1, 'tag=frizonse-21'), $url1);

// --- buildAffiliateUrl: replaces existing tag ---
$url2 = $fetcher->buildAffiliateUrl('https://www.amazon.se/dp/B08N5WRWNW?tag=oldtag-20&ref=xyz');
check('old tag replaced',       str_contains($url2, 'tag=frizonse-21') && !str_contains($url2, 'oldtag-20'), $url2);
check('ref param preserved',    str_contains($url2, 'ref=xyz'), $url2);

// --- buildAffiliateUrl: preserves existing query params ---
$url3 = $fetcher->buildAffiliateUrl('https://www.amazon.se/dp/B08N5WRWNW?keywords=test');
check('keywords preserved',     str_contains($url3, 'keywords=test'), $url3);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 3: Run test to verify it passes**

```bash
php tests/test_amazon_shop.php
```

Expected output:
```
PASS: amazon.se is valid
PASS: amazon.com is valid
PASS: amazon.co.uk is valid
PASS: evil.com not valid
PASS: notamazon.se not valid
PASS: tag appended
PASS: old tag replaced
PASS: ref param preserved
PASS: keywords preserved

9 passed, 0 failed
```

- [ ] **Step 4: Commit**

```bash
git add app/Services/AmazonFetcher.php tests/test_amazon_shop.php
git commit -m "feat: add AmazonFetcher service with affiliate URL and og:meta extraction"
```

---

## Task 4: Extend AiService for shop

**Files:**
- Modify: `app/Services/AiService.php`

Add three methods to the `AiProviderInterface`, `ClaudeAiProvider`, and `FakeAiProvider`. Do not modify the existing methods.

- [ ] **Step 1: Add to `AiProviderInterface` (after the existing methods)**

```php
    public function generateShopDescription(array $context): string;
    public function generateShopSeo(array $product): array;
    public function translateToSwedish(string $text): string;
```

- [ ] **Step 2: Add to `ClaudeAiProvider` (before the `buildUserPrompt` method)**

```php
    public function generateShopDescription(array $context): string
    {
        $title       = $context['title'] ?? 'Produkten';
        $amazonDesc  = $context['amazon_description'] ?? '';
        $currentText = $context['current_text'] ?? '';

        $prompt = "Produkt: {$title}\n"
            . ($amazonDesc ? "Amazon-beskrivning: {$amazonDesc}\n" : '')
            . ($currentText ? "Nuvarande text: {$currentText}\n" : '')
            . "\nSkriv en personlig och säljande produktbeskrivning på svenska (2-3 stycken) som förklarar "
            . "varför vi gillar den, hur vi använder den på resan med vår husbil Frizze, och varför vi rekommenderar den. "
            . "Skriv ren löpande text utan markdown, inga **, ## eller liknande.";

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 600,
            'system'     => 'Du är en entusiastisk husbilsresenär som skriver produktrekommendationer på svenska. Skriv personligt, varmt och övertygande.',
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        return $this->callClaude($payload);
    }

    public function generateShopSeo(array $product): array
    {
        $title   = $product['title'] ?? '';
        $desc    = $product['amazon_description'] ?? $product['our_description'] ?? '';
        $category = $product['category'] ?? '';

        $prompt = "Produkt: {$title}\n"
            . ($category ? "Kategori: {$category}\n" : '')
            . ($desc ? "Beskrivning: " . mb_substr($desc, 0, 500) . "\n" : '')
            . "\nGenerera:\n"
            . "1. seo_title: En säljande sidtitel max 60 tecken på svenska, inkludera produktnamnet.\n"
            . "2. seo_description: En lockande meta-beskrivning max 155 tecken på svenska som beskriver produkten och nämner att det är en rekommendation från Frizon.\n\n"
            . "Svara ENBART med giltig JSON:\n"
            . '{"seo_title":"...","seo_description":"..."}';

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 300,
            'system'     => 'Du är en SEO-expert som skriver säljande produkttitlar och beskrivningar på svenska. Svara ALLTID med giltig JSON och inget annat.',
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        $text   = $this->callClaude($payload);
        $result = json_decode($text, true);

        if (!isset($result['seo_title'], $result['seo_description'])) {
            throw new RuntimeException('Ogiltigt JSON-svar från Claude vid shop SEO-generering.');
        }

        return [
            'seo_title'       => mb_substr((string) $result['seo_title'], 0, 60),
            'seo_description' => mb_substr((string) $result['seo_description'], 0, 155),
        ];
    }

    public function translateToSwedish(string $text): string
    {
        if (trim($text) === '') {
            return $text;
        }

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 500,
            'system'     => 'Du är en professionell översättare. Översätt texten till korrekt, naturlig svenska. Svara ENBART med den översatta texten, inget annat.',
            'messages'   => [['role' => 'user', 'content' => $text]],
        ];

        return $this->callClaude($payload);
    }
```

- [ ] **Step 3: Extract shared cURL logic in `ClaudeAiProvider`**

The three new methods above all call `$this->callClaude($payload)`. Add this private helper to `ClaudeAiProvider` (before `buildUserPrompt`):

```php
    private function callClaude(array $payload): string
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('cURL-fel vid anrop till Claude API: ' . $curlError);
        }
        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            throw new RuntimeException('Claude API-fel: ' . ($body['error']['message'] ?? 'HTTP ' . $httpCode));
        }

        $data = json_decode($response, true);
        $text = trim($data['content'][0]['text'] ?? '');
        if ($text === '') {
            throw new RuntimeException('Claude returnerade ett tomt svar.');
        }
        return $text;
    }
```

Also update the existing `generateDraft` and `generatePlaceSeo` methods in `ClaudeAiProvider` to use `$this->callClaude()` instead of the inline cURL block. Both methods do the same cURL setup — replace those blocks with `$text = $this->callClaude($payload);`.

- [ ] **Step 4: Add to `FakeAiProvider` (after `generatePlaceSeo`)**

```php
    public function generateShopDescription(array $context): string
    {
        $title = $context['title'] ?? 'Produkten';
        return "{$title} är en produkt vi verkligen gillar och använder under våra resor med Frizze.\n\n"
            . "Den har visat sig vara ett praktiskt inköp som vi gärna rekommenderar till andra husbilsresenärer. "
            . "Kvaliteten är bra och den är enkel att använda även när man befinner sig på resande fot.";
    }

    public function generateShopSeo(array $product): array
    {
        $title = $product['title'] ?? 'Produkt';
        $desc  = $product['amazon_description'] ?? $product['our_description'] ?? '';
        return [
            'seo_title'       => mb_substr($title . ' — Frizon rekommenderar', 0, 60),
            'seo_description' => mb_substr($desc ?: "Vi rekommenderar {$title} för husbilsresor.", 0, 155),
        ];
    }

    public function translateToSwedish(string $text): string
    {
        // Fake provider: return unchanged (assume already Swedish in dev)
        return $text;
    }
```

- [ ] **Step 5: Add public methods to `AiService` class (after existing public methods)**

```php
    public function generateShopDescription(array $context): string
    {
        return $this->provider->generateShopDescription($context);
    }

    public function generateShopSeo(array $product): array
    {
        return $this->provider->generateShopSeo($product);
    }

    public function translateToSwedish(string $text): string
    {
        return $this->provider->translateToSwedish($text);
    }
```

- [ ] **Step 6: Commit**

```bash
git add app/Services/AiService.php
git commit -m "feat: extend AiService with shop description, SEO, and translation methods"
```

---

## Task 5: AmazonController — admin CRUD

**Files:**
- Create: `app/Controllers/AmazonController.php` (skeleton + admin methods)

- [ ] **Step 1: Create the controller file with admin CRUD**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Services/AmazonFetcher.php';
require_once dirname(__DIR__) . '/Models/AmazonProduct.php';

class AmazonController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo    = $pdo;
        $this->config = $config;
    }

    // -------------------------------------------------------------------------
    // Admin: list
    // -------------------------------------------------------------------------

    public function adminIndex(array $params): void
    {
        Auth::requireLogin();
        $model    = new AmazonProduct($this->pdo);
        $products = $model->all();
        view('amazon/index', compact('products'), 'app');
    }

    // -------------------------------------------------------------------------
    // Admin: create form
    // -------------------------------------------------------------------------

    public function adminCreate(array $params): void
    {
        Auth::requireLogin();
        $categories = (new AmazonProduct($this->pdo))->allCategories();
        view('amazon/create', compact('categories'), 'app');
    }

    // -------------------------------------------------------------------------
    // Admin: store (POST)
    // -------------------------------------------------------------------------

    public function adminStore(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo 'Ogiltig säkerhetstoken.';
            return;
        }

        $title     = trim($_POST['title'] ?? '');
        $amazonUrl = trim($_POST['amazon_url'] ?? '');

        if ($title === '' || $amazonUrl === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Titel och Amazon-URL krävs.'];
            header('Location: /adm/amazon-lista/ny');
            return;
        }

        $fetcher = $this->makeFetcher();

        if (!$fetcher->isAmazonUrl($amazonUrl)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'URL:en måste vara en Amazon-domän.'];
            header('Location: /adm/amazon-lista/ny');
            return;
        }

        $affiliateUrl      = $fetcher->buildAffiliateUrl($amazonUrl);
        $imagePath         = null;
        $amazonDescription = null;

        // Auto-fetch meta from Amazon
        $meta = $fetcher->fetchProductMeta($amazonUrl);

        if ($meta['image_url']) {
            $filename = $fetcher->downloadImage($meta['image_url']);
            if ($filename) {
                $imagePath = $filename;
            }
        }

        if ($meta['description']) {
            // Translate to Swedish if needed
            $amazonDescription = $this->ensureSwedish($meta['description']);
        }

        // Generate SEO fields via AI
        $seoData = $this->generateSeo([
            'title'               => $title,
            'amazon_description'  => $amazonDescription,
            'our_description'     => trim($_POST['our_description'] ?? ''),
            'category'            => trim($_POST['category'] ?? ''),
        ]);

        $model = new AmazonProduct($this->pdo);
        $id    = $model->create([
            'slug'               => AmazonProduct::generateSlug($title),
            'title'              => $title,
            'amazon_url'         => $amazonUrl,
            'affiliate_url'      => $affiliateUrl,
            'image_path'         => $imagePath,
            'amazon_description' => $amazonDescription,
            'our_description'    => trim($_POST['our_description'] ?? '') ?: null,
            'seo_title'          => $seoData['seo_title'] ?? null,
            'seo_description'    => $seoData['seo_description'] ?? null,
            'category'           => trim($_POST['category'] ?? '') ?: null,
            'sort_order'         => (int) ($_POST['sort_order'] ?? 0),
            'is_featured'        => isset($_POST['is_featured']) ? 1 : 0,
            'is_published'       => isset($_POST['is_published']) ? 1 : 0,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produkt skapad.'];
        header('Location: /adm/amazon-lista/' . $id . '/redigera');
    }

    // -------------------------------------------------------------------------
    // Admin: edit form
    // -------------------------------------------------------------------------

    public function adminEdit(array $params): void
    {
        Auth::requireLogin();
        $id      = (int) ($params['id'] ?? 0);
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if (!$product) {
            http_response_code(404);
            echo '<h1>Produkten hittades inte</h1>';
            return;
        }

        $categories = $model->allCategories();
        view('amazon/edit', compact('product', 'categories'), 'app');
    }

    // -------------------------------------------------------------------------
    // Admin: update (PUT)
    // -------------------------------------------------------------------------

    public function adminUpdate(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo 'Ogiltig säkerhetstoken.';
            return;
        }

        $id    = (int) ($params['id'] ?? 0);
        $model = new AmazonProduct($this->pdo);
        $existing = $model->findById($id);

        if (!$existing) {
            http_response_code(404);
            echo '<h1>Produkten hittades inte</h1>';
            return;
        }

        $title     = trim($_POST['title'] ?? '');
        $amazonUrl = trim($_POST['amazon_url'] ?? '');

        if ($title === '' || $amazonUrl === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Titel och Amazon-URL krävs.'];
            header('Location: /adm/amazon-lista/' . $id . '/redigera');
            return;
        }

        $fetcher      = $this->makeFetcher();
        $affiliateUrl = $fetcher->buildAffiliateUrl($amazonUrl);
        $imagePath    = $existing['image_path'];
        $amazonDesc   = $existing['amazon_description'];

        // Re-fetch from Amazon if URL changed
        if ($amazonUrl !== $existing['amazon_url']) {
            if (!$fetcher->isAmazonUrl($amazonUrl)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'URL:en måste vara en Amazon-domän.'];
                header('Location: /adm/amazon-lista/' . $id . '/redigera');
                return;
            }

            $meta = $fetcher->fetchProductMeta($amazonUrl);

            if ($meta['image_url']) {
                $filename = $fetcher->downloadImage($meta['image_url']);
                if ($filename) {
                    $imagePath = $filename;
                }
            }

            if ($meta['description']) {
                $amazonDesc = $this->ensureSwedish($meta['description']);
            }
        }

        // Allow manual override of SEO fields; regenerate if empty
        $seoTitle = trim($_POST['seo_title'] ?? '');
        $seoDesc  = trim($_POST['seo_description'] ?? '');

        if ($seoTitle === '' || $seoDesc === '') {
            $seoData  = $this->generateSeo([
                'title'              => $title,
                'amazon_description' => $amazonDesc,
                'our_description'    => trim($_POST['our_description'] ?? ''),
                'category'           => trim($_POST['category'] ?? ''),
            ]);
            $seoTitle = $seoTitle ?: ($seoData['seo_title'] ?? '');
            $seoDesc  = $seoDesc  ?: ($seoData['seo_description'] ?? '');
        }

        $model->update($id, [
            'title'              => $title,
            'amazon_url'         => $amazonUrl,
            'affiliate_url'      => $affiliateUrl,
            'image_path'         => $imagePath,
            'amazon_description' => $amazonDesc,
            'our_description'    => trim($_POST['our_description'] ?? '') ?: null,
            'seo_title'          => $seoTitle ?: null,
            'seo_description'    => $seoDesc  ?: null,
            'category'           => trim($_POST['category'] ?? '') ?: null,
            'sort_order'         => (int) ($_POST['sort_order'] ?? 0),
            'is_featured'        => isset($_POST['is_featured']) ? 1 : 0,
            'is_published'       => isset($_POST['is_published']) ? 1 : 0,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produkt uppdaterad.'];
        header('Location: /adm/amazon-lista/' . $id . '/redigera');
    }

    // -------------------------------------------------------------------------
    // Admin: destroy (DELETE)
    // -------------------------------------------------------------------------

    public function adminDestroy(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            return;
        }

        $id    = (int) ($params['id'] ?? 0);
        $model = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if ($product && $product['image_path']) {
            $imagePath = dirname(__DIR__, 2) . '/storage/uploads/amazon/' . $product['image_path'];
            if (is_file($imagePath)) {
                unlink($imagePath);
            }
        }

        $model->delete($id);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produkt borttagen.'];
        header('Location: /adm/amazon-lista');
    }

    // -------------------------------------------------------------------------
    // Admin: AI "brodera ut" product description
    // -------------------------------------------------------------------------

    public function generateDraft(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken.']);
            return;
        }

        $id      = (int) ($params['id'] ?? 0);
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Produkten hittades inte.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $context = [
            'title'              => $product['title'],
            'amazon_description' => $product['amazon_description'] ?? '',
            'current_text'       => $input['current_text'] ?? $product['our_description'] ?? '',
        ];

        try {
            $ai   = new AiService();
            $text = $ai->generateShopDescription($context);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        echo json_encode(['success' => true, 'text' => $text]);
    }

    // -------------------------------------------------------------------------
    // Admin: categories autocomplete API
    // -------------------------------------------------------------------------

    public function categoriesApi(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $categories = (new AmazonProduct($this->pdo))->allCategories();
        echo json_encode($categories);
    }

    // -------------------------------------------------------------------------
    // Public: shop listing
    // -------------------------------------------------------------------------

    public function shopIndex(array $params): void
    {
        $model      = new AmazonProduct($this->pdo);
        $products   = $model->allPublished();
        $categories = $model->publishedCategories();

        $filterCategory = $_GET['kategori'] ?? null;
        $search         = trim($_GET['s'] ?? '');

        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
        $pageTitle = 'Shop — Frizon of Sweden';

        $seoMeta = [
            'description' => 'Produkter vi verkligen använder och rekommenderar för husbilsresor. Noggrant utvalda av Mattias och Ulrica på Frizon of Sweden.',
            'og_url'      => $appUrl . '/shop',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        $schemas = [[
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => 'Frizon Shop — rekommenderade produkter',
            'url'         => $appUrl . '/shop',
            'description' => 'Produkter vi rekommenderar för husbilsresor.',
            'inLanguage'  => 'sv',
        ]];

        view('public/shop', compact(
            'products', 'categories', 'filterCategory', 'search', 'pageTitle', 'seoMeta', 'schemas'
        ), 'public');
    }

    // -------------------------------------------------------------------------
    // Public: product detail
    // -------------------------------------------------------------------------

    public function shopProduct(array $params): void
    {
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findBySlug($params['slug']);

        if (!$product || !$product['is_published']) {
            http_response_code(404);
            echo '<h1>Produkten hittades inte</h1>';
            return;
        }

        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
        $pageTitle = ($product['seo_title'] ?: $product['title']) . ' — Frizon';

        $metaDesc = $product['seo_description']
            ?: ($product['our_description'] ? mb_strimwidth($product['our_description'], 0, 155, '...') : null)
            ?: 'Rekommenderas av Mattias och Ulrica på Frizon of Sweden.';

        $ogImage = $product['image_path']
            ? $appUrl . '/uploads/amazon/' . $product['image_path']
            : $appUrl . '/img/frizon-logo.png';

        $seoMeta = [
            'description' => $metaDesc,
            'og_url'      => $appUrl . '/shop/' . $product['slug'],
            'og_image'    => $ogImage,
        ];

        $schemas = [[
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product['title'],
            'description' => $product['amazon_description'] ?? $product['our_description'] ?? '',
            'url'         => $appUrl . '/shop/' . $product['slug'],
            'image'       => $ogImage,
            'offers'      => [
                '@type'       => 'Offer',
                'url'         => $product['affiliate_url'],
                'seller'      => ['@type' => 'Organization', 'name' => 'Amazon'],
                'availability'=> 'https://schema.org/InStock',
            ],
        ]];

        view('public/shop-product', compact('product', 'pageTitle', 'seoMeta', 'schemas', 'ogImage'), 'public');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function makeFetcher(): AmazonFetcher
    {
        $associateId = $_ENV['AMAZON_ASSOCIATE_ID'] ?? '';
        $uploadDir   = dirname(__DIR__, 2) . '/storage/uploads/amazon';
        return new AmazonFetcher($associateId, $uploadDir);
    }

    private function ensureSwedish(string $text): string
    {
        // Heuristic: if text contains common non-Swedish characters or looks English, translate
        // In production with AI_PROVIDER=claude this calls the API; in fake mode returns unchanged
        if ($this->looksNonSwedish($text)) {
            try {
                $ai = new AiService();
                return $ai->translateToSwedish($text);
            } catch (RuntimeException) {
                return $text; // graceful fallback
            }
        }
        return $text;
    }

    private function looksNonSwedish(string $text): bool
    {
        // Simple heuristic: Swedish has å, ä, ö fairly often, plus common Swedish words
        $hasSwedishChars = preg_match('/[åäöÅÄÖ]/', $text);
        $hasSwedishWords = preg_match('/\b(och|med|för|till|från|är|det|vi|att)\b/iu', $text);
        return !$hasSwedishChars && !$hasSwedishWords;
    }

    private function generateSeo(array $productData): array
    {
        try {
            $ai = new AiService();
            return $ai->generateShopSeo($productData);
        } catch (RuntimeException) {
            // Fallback: build simple SEO from title
            return [
                'seo_title'       => mb_substr($productData['title'] . ' — Frizon rekommenderar', 0, 60),
                'seo_description' => mb_substr(
                    $productData['amazon_description'] ?? "Vi rekommenderar {$productData['title']} för husbilsresor.",
                    0, 155
                ),
            ];
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Controllers/AmazonController.php
git commit -m "feat: add AmazonController with admin CRUD, AI draft, and public shop routes"
```

---

## Task 6: Admin views

**Files:**
- Create: `views/amazon/index.php`
- Create: `views/amazon/create.php`
- Create: `views/amazon/edit.php`

- [ ] **Step 1: Create `views/amazon/index.php`**

```php
<div class="page-header mb-4">
    <h2>Amazon-lista</h2>
    <a href="/adm/amazon-lista/ny" class="btn btn-primary btn--sm">+ Ny produkt</a>
</div>

<?php if (empty($products)): ?>
    <p class="text-muted" style="padding:var(--space-6) 0; font-style:italic;">Inga produkter än — lägg till din första!</p>
<?php else: ?>
<div style="overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; font-size:var(--text-sm);">
        <thead>
            <tr style="border-bottom:2px solid var(--color-border); text-align:left;">
                <th style="padding:var(--space-2) var(--space-3);">Bild</th>
                <th style="padding:var(--space-2) var(--space-3);">Titel</th>
                <th style="padding:var(--space-2) var(--space-3);">Kategori</th>
                <th style="padding:var(--space-2) var(--space-3);">Ordning</th>
                <th style="padding:var(--space-2) var(--space-3);">Featured</th>
                <th style="padding:var(--space-2) var(--space-3);">Publicerad</th>
                <th style="padding:var(--space-2) var(--space-3);"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr style="border-bottom:1px solid var(--color-border);">
                <td style="padding:var(--space-2) var(--space-3);">
                    <?php if ($p['image_path']): ?>
                        <img src="/uploads/amazon/<?= htmlspecialchars($p['image_path']) ?>"
                             alt="" style="width:48px; height:48px; object-fit:cover; border-radius:var(--radius-sm);">
                    <?php else: ?>
                        <div style="width:48px; height:48px; background:var(--color-bg-muted); border-radius:var(--radius-sm);"></div>
                    <?php endif; ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3); font-weight:var(--weight-medium);">
                    <?= htmlspecialchars($p['title']) ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3); color:var(--color-text-muted);">
                    <?= htmlspecialchars($p['category'] ?? '—') ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3);">
                    <?= (int) $p['sort_order'] ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3);">
                    <?= $p['is_featured'] ? '★' : '—' ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3);">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?= $p['is_published'] ? 'var(--color-success,#22c55e)' : 'var(--color-border)' ?>;"></span>
                    <?= $p['is_published'] ? 'Ja' : 'Nej' ?>
                </td>
                <td style="padding:var(--space-2) var(--space-3); white-space:nowrap;">
                    <a href="/adm/amazon-lista/<?= (int) $p['id'] ?>/redigera" class="btn btn-ghost btn--sm">Redigera</a>
                    <a href="/shop/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="btn btn-ghost btn--sm">Visa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
```

- [ ] **Step 2: Create `views/amazon/create.php`**

```php
<div class="page-header mb-4">
    <a href="/adm/amazon-lista" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Ny produkt</h2>
</div>

<form method="POST" action="/adm/amazon-lista" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="title" class="form-label">Titel *</label>
        <input type="text" id="title" name="title" class="form-input" required
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="amazon_url" class="form-label">Amazon-URL *</label>
        <input type="url" id="amazon_url" name="amazon_url" class="form-input" required
               placeholder="https://www.amazon.se/dp/..."
               value="<?= htmlspecialchars($_POST['amazon_url'] ?? '') ?>">
        <p class="form-hint">Bild och beskrivning hämtas automatiskt vid sparande. Affiliatelänk genereras automatiskt.</p>
    </div>

    <div class="form-group">
        <label for="category" class="form-label">Kategori</label>
        <input type="text" id="category" name="category" class="form-input"
               placeholder="t.ex. Kök, Elektronik, Säkerhet"
               value="<?= htmlspecialchars($_POST['category'] ?? '') ?>"
               list="category-suggestions">
        <datalist id="category-suggestions">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--space-2);">
            <label for="our_description" class="form-label" style="margin:0;">Vår beskrivning</label>
            <button type="button" id="ai-desc-btn" class="btn btn-secondary btn--sm" style="font-size:var(--text-xs);" disabled>Brodera ut text</button>
        </div>
        <textarea id="our_description" name="our_description" class="form-textarea" rows="5"
                  placeholder="Skriv varför ni gillar produkten och hur ni använder den..."><?= htmlspecialchars($_POST['our_description'] ?? '') ?></textarea>
        <p id="ai-desc-status" style="font-size:var(--text-sm); color:var(--color-text-muted); margin-top:var(--space-1); display:none;"></p>
        <p class="form-hint">Spara produkten först för att aktivera AI-broderi (behöver produkt-ID).</p>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3);">
        <div class="form-group">
            <label for="sort_order" class="form-label">Sorteringsordning</label>
            <input type="number" id="sort_order" name="sort_order" class="form-input"
                   value="<?= (int) ($_POST['sort_order'] ?? 0) ?>" min="0">
        </div>
    </div>

    <div style="display:flex; gap:var(--space-4); margin-bottom:var(--space-4);">
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_featured" value="1" <?= !empty($_POST['is_featured']) ? 'checked' : '' ?>>
            <span>Featured</span>
        </label>
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_published" value="1" <?= !empty($_POST['is_published']) ? 'checked' : '' ?>>
            <span>Publicerad</span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary">Spara och hämta info från Amazon</button>
</form>
```

- [ ] **Step 3: Create `views/amazon/edit.php`**

```php
<div class="page-header mb-4">
    <a href="/adm/amazon-lista" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera: <?= htmlspecialchars($product['title']) ?></h2>
</div>

<form method="POST" action="/adm/amazon-lista/<?= (int) $product['id'] ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <?php if ($product['image_path']): ?>
    <div class="form-group">
        <label class="form-label">Produktbild (hämtad från Amazon)</label>
        <img src="/uploads/amazon/<?= htmlspecialchars($product['image_path']) ?>"
             alt="<?= htmlspecialchars($product['title']) ?>"
             style="max-width:200px; max-height:200px; object-fit:contain; border-radius:var(--radius-md); border:1px solid var(--color-border);">
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="title" class="form-label">Titel *</label>
        <input type="text" id="title" name="title" class="form-input" required
               value="<?= htmlspecialchars($product['title']) ?>">
    </div>

    <div class="form-group">
        <label for="amazon_url" class="form-label">Amazon-URL *</label>
        <input type="url" id="amazon_url" name="amazon_url" class="form-input" required
               value="<?= htmlspecialchars($product['amazon_url']) ?>">
        <p class="form-hint">Om URL:en ändras hämtas ny bild och beskrivning automatiskt.</p>
    </div>

    <div class="form-group">
        <label class="form-label">Affiliatelänk (auto-genererad)</label>
        <input type="text" class="form-input" readonly value="<?= htmlspecialchars($product['affiliate_url']) ?>"
               style="color:var(--color-text-muted); font-size:var(--text-sm);">
    </div>

    <?php if ($product['amazon_description']): ?>
    <div class="form-group">
        <label class="form-label">Amazon-beskrivning (hämtad, på svenska)</label>
        <textarea class="form-textarea" rows="3" readonly
                  style="color:var(--color-text-muted); font-size:var(--text-sm);"><?= htmlspecialchars($product['amazon_description']) ?></textarea>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="category" class="form-label">Kategori</label>
        <input type="text" id="category" name="category" class="form-input"
               value="<?= htmlspecialchars($product['category'] ?? '') ?>"
               list="category-suggestions">
        <datalist id="category-suggestions">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>">
            <?php endforeach; ?>
        </datalist>
    </div>

    <div class="form-group">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--space-2);">
            <label for="our_description" class="form-label" style="margin:0;">Vår beskrivning</label>
            <button type="button" id="ai-desc-btn" class="btn btn-secondary btn--sm" style="font-size:var(--text-xs);">Brodera ut text</button>
        </div>
        <textarea id="our_description" name="our_description" class="form-textarea" rows="5"><?= htmlspecialchars($product['our_description'] ?? '') ?></textarea>
        <p id="ai-desc-status" style="font-size:var(--text-sm); color:var(--color-text-muted); margin-top:var(--space-1); display:none;"></p>
    </div>

    <div style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
        <h3 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-2);">SEO</h3>
        <p style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-4);">Auto-genereras vid sparande om fälten lämnas tomma.</p>

        <div class="form-group">
            <label for="seo_title" class="form-label">
                SEO-titel
                <span id="seo-title-count" style="color:var(--color-text-muted); font-weight:normal;">(<?= mb_strlen($product['seo_title'] ?? '') ?>/60)</span>
            </label>
            <input type="text" id="seo_title" name="seo_title" class="form-input" maxlength="60"
                   value="<?= htmlspecialchars($product['seo_title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="seo_description" class="form-label">
                SEO-beskrivning
                <span id="seo-desc-count" style="color:var(--color-text-muted); font-weight:normal;">(<?= mb_strlen($product['seo_description'] ?? '') ?>/155)</span>
            </label>
            <input type="text" id="seo_description" name="seo_description" class="form-input" maxlength="155"
                   value="<?= htmlspecialchars($product['seo_description'] ?? '') ?>">
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3); margin-top:var(--space-4);">
        <div class="form-group">
            <label for="sort_order" class="form-label">Sorteringsordning</label>
            <input type="number" id="sort_order" name="sort_order" class="form-input"
                   value="<?= (int) $product['sort_order'] ?>" min="0">
        </div>
    </div>

    <div style="display:flex; gap:var(--space-4); margin-bottom:var(--space-4);">
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
            <span>Featured</span>
        </label>
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
            <input type="checkbox" name="is_published" value="1" <?= $product['is_published'] ? 'checked' : '' ?>>
            <span>Publicerad</span>
        </label>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/adm/amazon-lista" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/adm/amazon-lista/<?= (int) $product['id'] ?>"
      style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm"
            data-confirm="Är du säker? Produkten tas bort permanent.">Ta bort produkt</button>
</form>

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function () {
    var aiBtn    = document.getElementById('ai-desc-btn');
    var descField = document.getElementById('our_description');
    var aiStatus  = document.getElementById('ai-desc-status');

    if (aiBtn && descField) {
        aiBtn.addEventListener('click', function () {
            aiBtn.disabled = true;
            aiBtn.textContent = 'Genererar...';
            aiStatus.style.display = 'block';
            aiStatus.textContent = 'Skapar beskrivning med AI...';

            var csrf = document.querySelector('input[name="_csrf"]');
            fetch('/adm/amazon-lista/<?= (int) $product['id'] ?>/ai/generera', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf ? csrf.value : ''
                },
                body: JSON.stringify({ current_text: descField.value })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    descField.value = data.text;
                    aiStatus.textContent = 'Utkast infogat — redigera och spara.';
                } else {
                    aiStatus.textContent = data.error || 'Något gick fel.';
                }
            })
            .catch(function () {
                aiStatus.textContent = 'Nätverksfel — försök igen.';
            })
            .finally(function () {
                aiBtn.disabled = false;
                aiBtn.textContent = 'Brodera ut text';
            });
        });
    }

    // SEO char counters
    var seoTitle = document.getElementById('seo_title');
    var seoTitleCount = document.getElementById('seo-title-count');
    if (seoTitle && seoTitleCount) {
        seoTitle.addEventListener('input', function () {
            seoTitleCount.textContent = '(' + seoTitle.value.length + '/60)';
        });
    }

    var seoDesc = document.getElementById('seo_description');
    var seoDescCount = document.getElementById('seo-desc-count');
    if (seoDesc && seoDescCount) {
        seoDesc.addEventListener('input', function () {
            seoDescCount.textContent = '(' + seoDesc.value.length + '/155)';
        });
    }
});
</script>
```

- [ ] **Step 4: Commit**

```bash
git add views/amazon/
git commit -m "feat: add admin views for amazon product CRUD"
```

---

## Task 7: Public shop views

**Files:**
- Create: `views/public/shop.php`
- Create: `views/public/shop-product.php`
- Modify: `public/css/pages/public.css`

- [ ] **Step 1: Create `views/public/shop.php`**

```php
<!-- Shop header -->
<div style="max-width:680px; margin:0 auto; padding:var(--space-8) var(--space-4) var(--space-4); text-align:center;">
    <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-3); color:var(--color-text);">Våra favoritprodukter</h1>
    <p style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text-muted); margin-bottom:var(--space-4);">
        Saker vi faktiskt använder på resan med Frizze — noggrant utvalda och personligen testade.
    </p>
    <!-- Affiliate disclaimer -->
    <p style="font-size:var(--text-sm); color:var(--color-text-muted); background:var(--color-bg-muted,#f5f5f4); padding:var(--space-3) var(--space-4); border-radius:var(--radius-md); border-left:3px solid var(--color-border);">
        Vi kan tjäna provision på köp via våra länkar — vi rekommenderar bara saker vi själva använder och gillar.
    </p>
</div>

<?php if (!empty($products)): ?>

<!-- Category filter -->
<?php if (count($categories) > 1): ?>
<div class="public-filters">
    <div class="filter-bar">
        <a href="/shop" class="filter-bar__chip <?= !$filterCategory ? 'is-active' : '' ?>">Alla</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/shop?kategori=<?= urlencode($cat) ?>"
               class="filter-bar__chip <?= $filterCategory === $cat ? 'is-active' : '' ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Search -->
<div style="max-width:var(--content-max-width); margin:0 auto; padding:0 var(--space-4) var(--space-4);">
    <input type="search" id="shop-search" class="form-input"
           placeholder="Sök bland produkter..."
           value="<?= htmlspecialchars($search) ?>"
           style="max-width:320px;">
</div>

<?php
// Featured products
$featured = array_filter($products, fn($p) => $p['is_featured']);
$regular  = array_filter($products, fn($p) => !$p['is_featured']);

// Apply category filter
if ($filterCategory) {
    $featured = array_filter($featured, fn($p) => $p['category'] === $filterCategory);
    $regular  = array_filter($regular,  fn($p) => $p['category'] === $filterCategory);
}
$featured = array_values($featured);
$regular  = array_values($regular);
?>

<?php if (!empty($featured) && !$filterCategory): ?>
<!-- Featured section -->
<section style="max-width:var(--content-max-width); margin:0 auto var(--space-6); padding:0 var(--space-4);">
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">★ Utvalda favoriter</h2>
    <div class="place-grid" id="featured-grid">
        <?php foreach ($featured as $p): ?>
            <?php include __DIR__ . '/../../views/partials/shop-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- All / filtered products -->
<?php $allVisible = array_merge($filterCategory ? [] : [], $filterCategory ? $featured : [], $regular); ?>
<?php $allVisible = $filterCategory ? array_merge($featured, $regular) : $regular; ?>
<section style="max-width:var(--content-max-width); margin:0 auto; padding:0 var(--space-4) var(--space-8);">
    <?php if ($filterCategory || !empty($featured)): ?>
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
        <?= $filterCategory ? htmlspecialchars($filterCategory) : 'Alla produkter' ?>
    </h2>
    <?php endif; ?>
    <div class="place-grid" id="shop-grid">
        <?php foreach ($allVisible as $p): ?>
            <?php include __DIR__ . '/../../views/partials/shop-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <p id="no-results" style="display:none; color:var(--color-text-muted); font-style:italic; padding:var(--space-4) 0;">Inga produkter matchar sökningen.</p>
</section>

<script<?= app_csp_nonce_attr() ?>>
(function () {
    var searchInput = document.getElementById('shop-search');
    if (!searchInput) return;

    function filterCards(query) {
        var q = query.toLowerCase().trim();
        var cards = document.querySelectorAll('.shop-card');
        var visible = 0;
        cards.forEach(function (card) {
            var title = (card.dataset.title || '').toLowerCase();
            var show  = !q || title.includes(q);
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        var noResults = document.getElementById('no-results');
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', function () {
        filterCards(this.value);
    });

    // Apply initial value (e.g. on back-navigation)
    if (searchInput.value) filterCards(searchInput.value);
})();
</script>

<?php else: ?>
<p style="text-align:center; padding:var(--space-8) var(--space-4); color:var(--color-text-muted); font-style:italic;">
    Inga produkter just nu — kom tillbaka snart!
</p>
<?php endif; ?>
```

- [ ] **Step 2: Create the shop card partial `views/partials/shop-card.php`**

```php
<a href="/shop/<?= htmlspecialchars($p['slug']) ?>"
   class="pub-place-card shop-card"
   data-title="<?= htmlspecialchars($p['title']) ?>">
    <?php if ($p['is_featured']): ?>
        <span class="pub-place-card__featured">★ Utvald</span>
    <?php endif; ?>
    <?php if ($p['image_path']): ?>
        <div class="shop-card__img-wrap">
            <img src="/uploads/amazon/<?= htmlspecialchars($p['image_path']) ?>"
                 alt="<?= htmlspecialchars($p['title']) ?>"
                 loading="lazy"
                 style="width:100%; height:160px; object-fit:contain; background:#fff; padding:var(--space-2);">
        </div>
    <?php endif; ?>
    <div class="pub-place-card__body">
        <?php if ($p['category']): ?>
            <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:var(--space-1);">
                <?= htmlspecialchars($p['category']) ?>
            </div>
        <?php endif; ?>
        <h3 class="pub-place-card__name"><?= htmlspecialchars($p['title']) ?></h3>
        <?php if ($p['our_description']): ?>
            <p class="pub-place-card__desc"><?= htmlspecialchars(mb_strimwidth($p['our_description'], 0, 100, '...')) ?></p>
        <?php endif; ?>
        <div style="margin-top:var(--space-3);">
            <span class="btn btn-primary btn--sm" style="pointer-events:none;">Se hos Amazon →</span>
        </div>
    </div>
</a>
```

- [ ] **Step 3: Create `views/public/shop-product.php`**

```php
<article style="max-width:720px; margin:0 auto; padding:var(--space-6) var(--space-4) var(--space-10);">

    <!-- Breadcrumb -->
    <nav style="font-size:var(--text-sm); color:var(--color-text-muted); margin-bottom:var(--space-6);">
        <a href="/shop" style="color:var(--color-text-muted); text-decoration:underline;">Shop</a>
        <span style="margin:0 var(--space-2);">›</span>
        <?php if ($product['category']): ?>
            <a href="/shop?kategori=<?= urlencode($product['category']) ?>"
               style="color:var(--color-text-muted); text-decoration:underline;"><?= htmlspecialchars($product['category']) ?></a>
            <span style="margin:0 var(--space-2);">›</span>
        <?php endif; ?>
        <span><?= htmlspecialchars($product['title']) ?></span>
    </nav>

    <div style="display:grid; grid-template-columns:1fr; gap:var(--space-6);">
        <?php if ($product['image_path']): ?>
        <div style="text-align:center; background:#fff; border-radius:var(--radius-lg); padding:var(--space-4); border:1px solid var(--color-border);">
            <img src="/uploads/amazon/<?= htmlspecialchars($product['image_path']) ?>"
                 alt="<?= htmlspecialchars($product['title']) ?>"
                 style="max-width:100%; max-height:320px; object-fit:contain;">
        </div>
        <?php endif; ?>

        <div>
            <?php if ($product['category']): ?>
            <p style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:var(--space-2);">
                <?= htmlspecialchars($product['category']) ?>
            </p>
            <?php endif; ?>

            <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-4); line-height:var(--leading-tight);">
                <?= htmlspecialchars($product['title']) ?>
            </h1>

            <?php if ($product['our_description']): ?>
            <div style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text); margin-bottom:var(--space-6);">
                <?php foreach (explode("\n\n", $product['our_description']) as $para): ?>
                    <p style="margin-bottom:var(--space-3);"><?= htmlspecialchars($para) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- CTA -->
            <a href="<?= htmlspecialchars($product['affiliate_url']) ?>"
               target="_blank" rel="noopener sponsored"
               class="btn btn-primary"
               style="display:inline-block; font-size:var(--text-base); padding:var(--space-3) var(--space-6);">
                Köp hos Amazon →
            </a>

            <!-- Disclaimer -->
            <p style="margin-top:var(--space-3); font-size:var(--text-xs); color:var(--color-text-muted);">
                Affiliatelänk — vi kan tjäna provision på köp, utan extra kostnad för dig.
            </p>
        </div>
    </div>

    <?php if ($product['amazon_description']): ?>
    <div style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
        <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">Om produkten</h2>
        <p style="font-size:var(--text-sm); line-height:var(--leading-relaxed); color:var(--color-text-muted);">
            <?= htmlspecialchars($product['amazon_description']) ?>
        </p>
    </div>
    <?php endif; ?>

    <div style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
        <a href="/shop" style="color:var(--color-text-muted); font-size:var(--text-sm); text-decoration:underline;">← Tillbaka till shoppen</a>
    </div>

</article>
```

- [ ] **Step 4: Add shop styles to `public/css/pages/public.css`**

Append at the end of `public/css/pages/public.css`:

```css
/* ── Shop ─────────────────────────────────────────────────── */
.shop-card__img-wrap {
    border-bottom: 1px solid var(--color-border);
    background: #fff;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    overflow: hidden;
}
```

- [ ] **Step 5: Commit**

```bash
git add views/public/shop.php views/public/shop-product.php views/partials/shop-card.php public/css/pages/public.css
git commit -m "feat: add public shop and product detail views"
```

---

## Task 8: Navigation, homepage teaser, and image serving

**Files:**
- Modify: `views/layouts/public.php`
- Modify: `views/partials/nav-desktop.php`
- Modify: `views/public/homepage.php`
- Modify: `public/index.php`

- [ ] **Step 1: Add "Shop" to public header in `views/layouts/public.php`**

Find the header links block:

```php
            <a href="/" class="public-header__link" style="font-weight:var(--weight-semibold);">Platser</a>
            <a href="/" style="text-decoration:none; flex-shrink:0;">
```

Replace with:

```php
            <div style="display:flex; gap:var(--space-4); align-items:center;">
                <a href="/" class="public-header__link" style="font-weight:var(--weight-semibold);">Platser</a>
                <a href="/shop" class="public-header__link <?= str_starts_with($reqPath, '/shop') ? 'public-header__link--active' : '' ?>" style="font-weight:var(--weight-semibold);">Shop</a>
            </div>
            <a href="/" style="text-decoration:none; flex-shrink:0;">
```

- [ ] **Step 2: Add "Shop" to admin sidebar in `views/partials/nav-desktop.php`**

Find the "Publicera" link:

```php
        <a href="/adm/publicera" class="sidebar-nav__item ...
```

Add before it:

```php
        <a href="/adm/amazon-lista" class="sidebar-nav__item <?= str_starts_with($currentPath, '/adm/amazon-lista') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Shop
        </a>
```

- [ ] **Step 3: Add "Nytt i shoppen" teaser to `views/public/homepage.php`**

In `PublicController::homepage()`, the view needs `$shopTeaser` data. Add this query to `homepage()` in `app/Controllers/PublicController.php` (just before the `view(...)` call):

```php
        // Shop teaser: 3 latest published products
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $shopTeaser = (new AmazonProduct($this->pdo))->latestPublished(3);
```

Add `$shopTeaser` to the compact() call:

```php
        view('public/homepage', compact('places', 'filterType', 'filterCountry', 'allPublic', 'allTypes', 'shopTeaser', 'pageTitle', 'seoMeta', 'schemas'), 'public');
```

Then at the bottom of `views/public/homepage.php`, before the closing `?>`, add:

```php
<?php if (!empty($shopTeaser)): ?>
<!-- Shop teaser -->
<section style="max-width:var(--content-max-width); margin:var(--space-8) auto var(--space-6); padding:0 var(--space-4);">
    <div style="display:flex; align-items:baseline; justify-content:space-between; margin-bottom:var(--space-4);">
        <h2 style="font-size:var(--text-xl); font-weight:var(--weight-bold); color:var(--color-text);">Nytt i shoppen</h2>
        <a href="/shop" style="font-size:var(--text-sm); color:var(--color-accent); text-decoration:none; font-weight:var(--weight-medium);">Se alla →</a>
    </div>
    <div class="place-grid">
        <?php foreach ($shopTeaser as $p): ?>
            <?php include dirname(__DIR__) . '/partials/shop-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
```

- [ ] **Step 4: Extend image serving in `public/index.php`**

Find:

```php
if (preg_match('#^/uploads/(thumbnails|cards|detail)/([^/]+)$#', $uri, $m)) {
```

Replace with:

```php
if (preg_match('#^/uploads/(thumbnails|cards|detail|amazon)/([^/]+)$#', $uri, $m)) {
```

- [ ] **Step 5: Commit**

```bash
git add views/layouts/public.php views/partials/nav-desktop.php views/public/homepage.php app/Controllers/PublicController.php public/index.php
git commit -m "feat: add shop to navigation, homepage teaser, and amazon image serving"
```

---

## Task 9: Routes and sitemap

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Controllers/PublicController.php` (sitemap method)

- [ ] **Step 1: Add routes to `routes/web.php`**

After the public pages block, add:

```php
    // Shop (public)
    $router->get('/shop', 'AmazonController', 'shopIndex');
    $router->get('/shop/{slug}', 'AmazonController', 'shopProduct');

    // Shop admin (behind auth, checked in controller)
    $router->get('/adm/amazon-lista', 'AmazonController', 'adminIndex');
    $router->get('/adm/amazon-lista/ny', 'AmazonController', 'adminCreate');
    $router->post('/adm/amazon-lista', 'AmazonController', 'adminStore');
    $router->get('/adm/amazon-lista/{id}/redigera', 'AmazonController', 'adminEdit');
    $router->put('/adm/amazon-lista/{id}', 'AmazonController', 'adminUpdate');
    $router->delete('/adm/amazon-lista/{id}', 'AmazonController', 'adminDestroy');
    $router->post('/adm/amazon-lista/{id}/ai/generera', 'AmazonController', 'generateDraft');
    $router->get('/adm/api/amazon/kategorier', 'AmazonController', 'categoriesApi');
```

Note: `/adm/amazon-lista/ny` must be registered **before** `/adm/amazon-lista/{id}/redigera` in the file to avoid slug collision. Since we use numeric IDs for the admin routes (`{id}`) and "ny" is not a number, the regex will not match, but placing it first is safer.

- [ ] **Step 2: Update `sitemap()` in `PublicController.php`**

Find the `sitemap()` method. After the block that adds place URLs, add:

```php
        // Shop pages
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $shopProducts = (new AmazonProduct($this->pdo))->allPublished();

        $urls[] = [
            'loc'     => $appUrl . '/shop',
            'lastmod' => date('Y-m-d'),
            'freq'    => 'weekly',
            'prio'    => '0.7',
        ];

        foreach ($shopProducts as $p) {
            $urls[] = [
                'loc'     => $appUrl . '/shop/' . $p['slug'],
                'lastmod' => date('Y-m-d', strtotime($p['updated_at'])),
                'freq'    => 'monthly',
                'prio'    => '0.6',
            ];
        }
```

(Verify that the existing sitemap method uses a `$urls` array and loops over it to build XML — if it uses a different structure, adapt accordingly.)

- [ ] **Step 3: Verify the sitemap output**

```bash
curl -s http://localhost/sitemap.xml | grep '/shop'
```

Expected: lines containing `/shop` and `/shop/{slug}` for each published product.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php app/Controllers/PublicController.php
git commit -m "feat: register shop routes and add shop URLs to sitemap"
```

---

## Task 10: Seed data

**Files:**
- Create: `database/seeds/amazon_products_seed.sql`

- [ ] **Step 1: Create seed file with 6 example products**

```sql
-- amazon_products_seed.sql
-- 6 example products across different categories.
-- Replace amazon_url / affiliate_url with real product links before use.
-- image_path is NULL — images will be fetched by AmazonFetcher on first edit.

INSERT INTO amazon_products
    (slug, title, amazon_url, affiliate_url, amazon_description, our_description,
     seo_title, seo_description, category, sort_order, is_featured, is_published)
VALUES
(
    'weber-q1200-gasolgrill-abc123',
    'Weber Q1200 Gasolgrill',
    'https://www.amazon.se/dp/B00004RALEN',
    'https://www.amazon.se/dp/B00004RALEN?tag=frizon-test-21',
    'Kompakt gasolgrill för balkonger och camping. 8 200 BTU effekt, gjutjärnsgaller.',
    'Weber Q1200 är vår go-to grill på resan. Den ryms enkelt i bakluckan och levererar perfekta grillresultat varje gång. Vi har använt den i över tre år och den håller fortfarande som ny.',
    'Weber Q1200 Gasolgrill — Frizon rekommenderar',
    'Vi rekommenderar Weber Q1200 för husbilsresor. Kompakt, kraftfull och enkel att ta med.',
    'Kök & Matlagning', 1, 1, 1
),
(
    'arlo-pro-4-kamera-def456',
    'Arlo Pro 4 Övervakningskamera',
    'https://www.amazon.se/dp/B08CQHKQDH',
    'https://www.amazon.se/dp/B08CQHKQDH?tag=frizon-test-21',
    'Trådlös 2K HDR-kamera med färgnattseende och inbyggd spotlight.',
    'Vi hänger upp Arlo Pro 4 utanför Frizze när vi är borta. Appen funkar bra även på mobilnätet och vi har blivit tryggare tack vare den.',
    'Arlo Pro 4 — säkerhetskamera för husbil',
    'Arlo Pro 4 ger trygghet när ni lämnar husbilen. Trådlös 2K-kamera med app-notiser.',
    'Säkerhet', 2, 1, 1
),
(
    'anker-powerbank-737-ghi789',
    'Anker 737 PowerBank 24000mAh',
    'https://www.amazon.se/dp/B09VPHVT2Z',
    'https://www.amazon.se/dp/B09VPHVT2Z?tag=frizon-test-21',
    '24 000 mAh, 140W snabbladdning, laddningstid 1,5 timmar.',
    'Den här powerbanken laddar allt — telefoner, laptop och till och med vår lilla fläkt. Ovärderlig på ställplatser utan el.',
    'Anker 737 PowerBank — laddning på resan',
    'Anker 737 med 24 000 mAh och 140W snabbladdning — perfekt för husbilsresor.',
    'Elektronik', 3, 0, 1
),
(
    'osprey-farpoint-40-ryggsack-jkl012',
    'Osprey Farpoint 40 Ryggsäck',
    'https://www.amazon.se/dp/B07GNQNCPF',
    'https://www.amazon.se/dp/B07GNQNCPF?tag=frizon-test-21',
    '40-liters ryggsäck med avtagbart dagsacksfack och ergonomisk ryggpanel.',
    'När vi lämnar Frizze för en dagstur är Osprey Farpoint vår ryggsäck. Den bär bekvämt hela dagen och passar handbagageutrymmet på flyget.',
    'Osprey Farpoint 40 — bästa dagsryggsäcken',
    'Osprey Farpoint 40 är perfekt för dagsturer från husbilen. Rymlig, bekväm och flygsäker.',
    'Packning & Väskor', 4, 0, 1
),
(
    'garmin-inreach-mini-2-mno345',
    'Garmin inReach Mini 2 Satellitkommunikator',
    'https://www.amazon.se/dp/B09FYNBK18',
    'https://www.amazon.se/dp/B09FYNBK18?tag=frizon-test-21',
    'Tvåvägs satellitmeddelanden och SOS-funktion, 14 dagars batteritid.',
    'Vi tar alltid med inReach Mini 2 i fjällterräng och avlägsna områden utan mobilnät. Det ger oss och hemmavarande trygghet att vi alltid går att nå.',
    'Garmin inReach Mini 2 — säkerhet i vildmarken',
    'Garmin inReach Mini 2 — satellit-SOS och meddelanden för husbilsresor i avlägsna områden.',
    'Säkerhet', 5, 0, 1
),
(
    'eva-solo-thermal-mug-pqr678',
    'Eva Solo Urban To Go Cup 0,35 l',
    'https://www.amazon.se/dp/B00MG7XXQG',
    'https://www.amazon.se/dp/B00MG7XXQG?tag=frizon-test-21',
    'Dubbelväggig termomugg i rostfritt stål, håller drycken varm i upp till 3 timmar.',
    'Ulrisas favorit. Den följer med på varje morgonpromenad och håller kaffet varmt länge nog för en lugn frukost utanför Frizze.',
    'Eva Solo Termomugg — morgonkaffet på resan',
    'Eva Solo Urban To Go Cup håller kaffet varmt på morgonpromenaden. Ulricas favorit på husbilsresan.',
    'Kök & Matlagning', 6, 0, 1
);
```

- [ ] **Step 2: Run the seed**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/seeds/amazon_products_seed.sql
```

- [ ] **Step 3: Verify in the browser**

Visit `http://localhost/shop` — should show 6 products. Visit `http://localhost/adm/amazon-lista` — should list all 6.

- [ ] **Step 4: Commit**

```bash
git add database/seeds/amazon_products_seed.sql
git commit -m "feat: add amazon shop seed data with 6 example products"
```

---

## Task 11: Bump service worker cache

**Files:**
- Modify: `public/sw.js`

- [ ] **Step 1: Find current CACHE_NAME in sw.js**

```bash
grep 'CACHE_NAME' public/sw.js
```

- [ ] **Step 2: Bump the version number**

If current value is e.g. `const CACHE_NAME = 'frizon-v3'`, change to `'frizon-v4'`. This forces PWA clients to pick up the new CSS.

- [ ] **Step 3: Commit**

```bash
git add public/sw.js
git commit -m "fix: bump SW cache to v4 for shop CSS and JS"
```

---

## Self-Review

**Spec coverage:**

| Spec requirement | Task |
|---|---|
| `amazon_products` table in MariaDB | Task 1 |
| AMAZON_ASSOCIATE_ID in .env | Task 1 |
| AmazonProduct model | Task 2 |
| Auto-fetch og:image on save | Task 3, 5 |
| Affiliate URL generation | Task 3, 5 |
| og:description fetched + translated | Task 3, 4, 5 |
| AI "brodera ut" for our_description | Task 4, 5, 6 |
| AI SEO generation (seo_title, seo_description) | Task 4, 5 |
| All admin CRUD routes | Task 5, 9 |
| All public routes | Task 5, 9 |
| Admin views: index, create, edit | Task 6 |
| Public views: shop listing, product detail | Task 7 |
| Category filter (chips) | Task 7 |
| Client-side search | Task 7 |
| Affiliate disclaimer | Task 7 |
| Product image displayed | Task 7 |
| "Se hos Amazon" CTA with affiliate link | Task 7 |
| JSON-LD Product schema on product detail | Task 5 |
| Canonical URL | via public layout |
| "Shop" in public nav header | Task 8 |
| "Shop" in admin sidebar | Task 8 |
| "Nytt i shoppen" homepage teaser | Task 8 |
| `/uploads/amazon/` image serving | Task 8 |
| All shop URLs in sitemap | Task 9 |
| Seed data (6 products, 2 featured) | Task 10 |
| SW cache bump | Task 11 |

**Type consistency:** `AmazonProduct::create()` and `AmazonProduct::update()` accept the same keys used in `AmazonController::adminStore()` and `adminUpdate()`. `generateShopSeo()` returns `['seo_title', 'seo_description']` which matches the keys read in both controller methods. ✓

**No placeholders found.** ✓

**Scope:** Fits comfortably in one plan. ✓
