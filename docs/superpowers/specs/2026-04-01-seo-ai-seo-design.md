# SEO + AI-SEO Design — frizon.org
_Date: 2026-04-01_

## Goal

Make all public content on frizon.org discoverable — by Google, by AI crawlers (ChatGPT, Perplexity, Claude), and by social sharing previews. Schema.org structured data is generated and published automatically when a place is published. Everything is editable post-publish from the place admin page.

---

## Publish flow

1. Admin clicks "Publicera" in the publish queue
2. Place goes live (`public_allowed = 1`) immediately
3. `PublishController::approve()` calls `AiService::generatePlaceSeo($place, $visits)`
4. Claude generates `meta_description` and `faq_content` — written directly to the `places` row
5. Admin can review and edit SEO fields at any time from the place admin page

No approval gate. SEO content is live immediately, editable later.

---

## Components

### 1. Meta tags (all public pages)

Added to `views/layouts/public.php`. Controllers pass optional variables:
- `$metaDescription` — used for `<meta name="description">` and `og:description`
- `$ogImage` — URL to first place image, fallback to `/img/frizon-logo.png`
- `$ogUrl` — canonical URL for this page

Outputs:
- `<meta name="description">`
- `<meta property="og:title|description|image|url|type">`
- `<meta name="twitter:card|title|description|image">`
- `<link rel="canonical">`

### 2. Schema.org JSON-LD

Output as `<script type="application/ld+json">` in layout `<head>`. Controllers build PHP arrays, layout serializes with `json_encode`.

**Homepage:** `WebSite` + `Person` (Mattias & Ulrica, Frizon of Sweden)

**Place detail:**
- `TouristAttraction` — always
- `AggregateRating` — only when at least one published visit has a rating
- `Review` entries — one per published visit that has both `total_rating_cached` AND `approved_public_text`
- `FAQPage` — only when `faq_content` is non-empty

**Toplist:** `ItemList` with ordered `ListItem` entries

Conditional inclusion logic lives in `PublicController`, not in views.

### 3. Sitemap.xml

Dynamic route `GET /sitemap.xml` handled by `PublicController::sitemap()`.
Queries all `public_allowed = 1` places. Outputs standard XML with:
- `<loc>` — full URL to place detail page
- `<lastmod>` — `updated_at` from places row
- Homepage, toplist, and static pages included as fixed entries

### 4. robots.txt

Static file at `public/robots.txt`:
```
User-agent: *
Allow: /
Disallow: /adm/

Sitemap: https://frizon.org/sitemap.xml
```

### 5. llms.txt

Dynamic route `GET /llms.txt` handled by `PublicController::llmsTxt()`.
Plain text, aimed at AI crawlers. Contains:
- Who Mattias and Ulrica are
- What Frizze is (Adria Twin, campervan travel)
- What the site covers (travel log, place reviews from a campervan perspective)
- List of all public places: name, type, country, one-line summary from `meta_description`

### 6. FAQ blocks on place detail

Rendered from `faq_content` JSON (array of `{q, a}` objects) as a `<section>` with `<dl>` pairs. Shown on the place detail page when content exists. Also drives the `FAQPage` schema.

### 7. AI generation on publish

`AiService` gets a new method:
```php
public function generatePlaceSeo(array $place, array $visits): array
// returns ['meta_description' => string, 'faq_content' => array]
```

Both `ClaudeAiProvider` and `FakeAiProvider` implement this. Claude makes one structured call asking for both outputs. The prompt includes: place name, type, country, `default_public_text`, all visit approved texts, ratings, `suitable_for`, `tips_notes`, `price_level`.

`PublishController::approve()` calls this after setting `public_allowed = 1`, then writes results to `places`.

### 8. Place admin edit fields

Two new fields on the place edit form:
- `meta_description` — text input, 255 char max, with live character counter
- `faq_content` — editable as structured field pairs (question + answer rows), stored as JSON

---

## Database

```sql
ALTER TABLE places
    ADD COLUMN meta_description VARCHAR(255) NULL AFTER default_public_text,
    ADD COLUMN faq_content JSON NULL AFTER meta_description;
```

---

## New routes

```
GET /sitemap.xml   → PublicController::sitemap()
GET /llms.txt      → PublicController::llmsTxt()
```

`public/robots.txt` — static file, no route needed.

---

## What is NOT changing

- `ai_drafts` table — not used for SEO content, which lives directly on `places`
- Existing AI draft flow for visit descriptions — unchanged
- Public place visibility logic — unchanged (`public_allowed = 1`)
- Private admin side — no SEO concerns

---

## Out of scope

- hreflang (site is Swedish-only)
- og:image generation / resizing (uses existing uploaded image or logo fallback)
- Structured data for trips (private, not public)
