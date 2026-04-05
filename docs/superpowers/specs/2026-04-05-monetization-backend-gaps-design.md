# Monetization & Backend Gaps — Design Spec

**Date:** 2026-04-05
**Status:** Approved
**Scope:** Frontend monetization improvements + backend infrastructure gaps

---

## Overview

frizon.org is a newly launched campervan travel log with a passive monetization goal. The primary revenue stream is Amazon Associates affiliate links via an integrated shop. The audience arrives from Instagram/Facebook and consists of existing campers and RV owners — high purchase intent. This spec covers three areas: maximizing affiliate revenue, building audience ownership, and closing remaining backend gaps.

---

## Section 1: Affiliate Revenue Layer

### 1.1 Click-Tracking Redirect — `/go/{slug}`

Every affiliate link routes through a first-party redirect endpoint before forwarding the user to the affiliate URL.

**Flow:**
1. User clicks any affiliate link (frontend links to `/go/{product-slug}`)
2. Server logs a row to `product_clicks` table: `product_id`, `referrer_path` (the page they clicked from), `clicked_at`, `user_agent`
3. Server issues a `302` redirect to the stored `affiliate_url`

**New table:**
```sql
CREATE TABLE product_clicks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  INT UNSIGNED NOT NULL,
    referrer    VARCHAR(500),
    clicked_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_agent  VARCHAR(500),
    INDEX idx_product (product_id),
    INDEX idx_clicked_at (clicked_at)
);
```

No personal data stored. No cookies. GDPR-clean.

All existing affiliate link `<a href>` tags in shop views must be updated to `/go/{slug}`.

---

### 1.2 Contextual Product Links on Place Detail Pages

Link specific products to specific places so engaged readers see relevant recommendations in context.

**New join table:**
```sql
CREATE TABLE place_products (
    place_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    note        VARCHAR(255),     -- optional: "använde detta här"
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (place_id, product_id)
);
```

**Admin UI change:** On the place edit page, add a product multi-select/search widget (AJAX search against published products). Allows attaching products with an optional note.

**Public UI change:** On `place-detail.php`, add a "Produkter vi använde här" section below the visit content. Renders product cards (image, title, note) linking to `/go/{slug}`. Hidden if no products are attached.

---

### 1.3 Multi-Program Affiliate Support

Expand beyond Amazon to Swedish/EU affiliate programs (Adtraction, Awin, Camping.se, XXL, Dometic, Thule, Biltema etc).

**Schema change to `amazon_products`:**
- Rename table to `products` (migration with data preserved)
- Add `affiliate_provider VARCHAR(50) DEFAULT 'amazon'`
- `affiliate_url` already exists — used as-is for non-Amazon providers

**Admin UI change:** For non-Amazon products, hide the "fetch from Amazon" button. Admin pastes in the affiliate URL directly. Image upload is manual (existing upload flow). Description is written manually or AI-assisted via existing "Brodera ut" button.

**AmazonFetcher service:** Rename to `ProductFetcher`. Amazon-specific fetch logic moves to a private method, only called when `affiliate_provider = 'amazon'`. PA-API replaces the og: scraper once AWS approval is granted — manual entry is the workflow until then.

---

### 1.4 Gear Guide Page — `/utrustning`

A static-ish public page: "Så är Frizze utrustad". Products are pulled from the DB filtered by a new `featured_gear TINYINT(1) DEFAULT 0` flag on the products table.

**Layout:** Sections by category (Kök & mat, Sovrum, Navigation, Utsida, Teknik). Each product renders as a card with image, title, short description, and a `/go/{slug}` link.

**Admin:** Toggle `featured_gear` on/off from the existing product edit page. No new admin view needed.

**SEO value:** Evergreen page. Answers "vad har ni i husbilen?" — a common search query in the Swedish campervan community. Zero ongoing maintenance once products are tagged.

---

## Section 2: Audience Ownership

### 2.1 Email Capture — Brevo Integration

**Signup form placement:**
- Footer (all public pages)
- Slide-in on place detail pages after 30 seconds (CSS transition, no JS library)

**Fields:** Email (required), first name (optional). Nothing else.

**Lead magnet:** "Frizze's packlista" — a formatted PDF of the actual packing/setup checklist. Pulled from list data already in the DB. One-time effort to design; Brevo delivers it automatically on double opt-in confirmation.

**Backend:** A single `POST /newsletter/subscribe` endpoint. Validates email, fires a POST to Brevo's Contacts API with email + list ID. Returns JSON success/fail. No email data stored in the local DB — Brevo owns the subscriber list.

**Config:** `BREVO_API_KEY` and `BREVO_LIST_ID` added to `.env`.

---

### 2.2 Contact / Sponsorship Page — `/samarbeta`

A simple form: name, company (optional), email, message.

**Spam protection — three layers, no third-party libraries:**

1. **Honeypot field:** A hidden `<input name="website">` styled with `display:none`. If it contains a value on submit, the request is silently discarded (bots fill it, humans don't).
2. **Timing check:** A hidden `<input name="ts">` populated with a signed timestamp on page load (`hash_hmac('sha256', $timestamp, APP_KEY)`). On submit, validate the signature and reject if less than 4 seconds have elapsed. Bots submit instantly.
3. **IP rate limiting:** Reuse `LoginThrottle` — max 3 submissions per IP per hour.

**Delivery:** AWS SES v2 via a minimal `SesMailer` service (cURL + SigV4, no SDK/Composer). Sender: `frizon@mobileminds.se` (whitelisted domain). Delivered to `CONTACT_EMAIL`. No DB storage of submissions.

**Page content:** A short "Vi samarbetar med varumärken vi faktiskt använder och rekommenderar" paragraph. This page is linked from the Instagram bio when ready.

---

### 2.3 "Kommande Resor" on Homepage

Show upcoming trips on the public homepage — only those explicitly opted in.

**Schema change:** Add `public_teaser TINYINT(1) DEFAULT 0` and `teaser_text VARCHAR(500)` to the `trips` table.

**Admin UI change:** On the trip edit page, a toggle "Visa som planerad resa publikt" + a free-text teaser field ("Vi planerar en Sverige-rund i sommar"). Default off. Specific stops are never exposed publicly.

**Public UI:** A "Kommande resor" section on the homepage, below the featured places map. Renders only trips where `public_teaser = 1` AND `start_date > TODAY`. Shows: teaser text, approximate start date (month + year, not exact date). Hidden entirely if no trips are opted in.

---

## Section 3: Backend Infrastructure Gaps

### 3.1 Admin Stats Dashboard — `/adm/statistik`

A read-only admin page showing:
- **Top places by views** (from `place_views` counter, see 3.2)
- **Top products by clicks** (from `product_clicks` table, last 30/90 days)
- **Top referrer pages** for product clicks (which place pages drive the most shop traffic)

No external analytics dependency. Simple `SELECT … GROUP BY … ORDER BY` queries.

---

### 3.2 Place View Tracking

A lightweight counter on every public place page load.

**Schema change:** Add `view_count INT UNSIGNED DEFAULT 0` to the `places` table.

**Implementation:** In `PublicController::showPlace()`, fire a single `UPDATE places SET view_count = view_count + 1 WHERE id = ?` after fetching the place. No session deduplication — this is a rough engagement signal, not analytics.

---

### 3.3 Amazon PA-API Migration (deferred)

The og: meta scraper in `AmazonFetcher` is non-functional. Manual product entry is the current workflow.

**When AWS Associate approval arrives:**
- Implement PA-API client as a private method in `ProductFetcher` (the renamed service)
- Replace the broken `fetchProductMeta()` implementation
- Keep `downloadImage()` and `buildAffiliateUrl()` unchanged
- The admin "Hämta från Amazon" button resumes working with real data

No fallback to scraping. Manual entry stays until PA-API is live.

---

### 3.4 CSRF Hardening (2 remaining findings)

From the security review, two controllers still missing CSRF validation on mutating endpoints:

- `ListController`: item toggle (`/adm/listor/{id}/items/{itemId}/toggle`), list reorder
- `TripController`: trip stop reorder

**Fix:** Add `CsrfService::verify()` call at the top of each affected action, consistent with how other controllers already handle it. The frontend already sends `X-CSRF-Token` on these requests.

---

### 3.5 Security Headers

Add to `public/.htaccess`:

```apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

CSP is already partially in place. Review and tighten inline script usage if possible.

---

### 3.6 RSS Feed — `/rss.xml`

A standard RSS 2.0 feed of newly published places.

**Content per item:** Place name, slug URL, published visit teaser (first 200 chars of the published visit notes), publish date, thumbnail image URL.

**Query:** `SELECT` places where `public_allowed = 1`, ordered by most recent published visit date, limit 20.

**Generated server-side** in `PublicController`. `Content-Type: application/rss+xml`. No caching layer needed at launch.

---

## Phasing

### Phase A — Now (passive, no ongoing maintenance)
1. CSRF fix on ListController + TripController (security hygiene, unblocks everything else)
2. Security headers in .htaccess
3. Click-tracking redirect `/go/{slug}` + `product_clicks` table
4. Update all affiliate link `<a href>` tags to use `/go/{slug}`
5. Place view tracking (`view_count` column + increment in PublicController)
6. `place_products` join table + admin widget + public widget on place detail
7. Contact/sponsorship page `/samarbeta` with spam protection
8. "Kommande resor" on homepage (`public_teaser` flag on trips)

### Phase B — After PA-API approval / when content is ready
1. Multi-program affiliate support (rename table, add `affiliate_provider`)
2. Gear guide page `/utrustning` (`featured_gear` flag + public view)
3. Brevo email capture + lead magnet PDF
4. Admin stats dashboard `/adm/statistik`
5. RSS feed `/rss.xml`
6. PA-API migration (when AWS approval granted)

---

## What This Does NOT Include

- Paid content / membership / paywalls
- Ad networks (no ads)
- YouTube / podcast infrastructure (future, separate spec)
- Email newsletter campaigns (Brevo handles that side; this spec only covers capture)
- Algorithmic product recommendations (manual curation only)
