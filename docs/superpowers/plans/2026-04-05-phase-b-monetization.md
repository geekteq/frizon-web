# Phase B — Monetization Expansion Implementation Plan

> **STATUS: PLACEHOLDER — not ready for implementation.**
> Detail this plan when Phase A is complete and AWS PA-API approval is received.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand affiliate coverage to multiple programs, add a gear guide page, capture emails via Brevo, build an admin stats dashboard, and add an RSS feed.

**Architecture:** TBD — detail when ready to implement. Each task is independent.

**Blocked on:** AWS Associates PA-API approval (required for Task 1).

---

## Scope

### Task 1: Amazon PA-API Migration
**Blocked on:** AWS Associates PA-API approval.

- Replace dead `AmazonFetcher` og:meta scraper with PA-API client
- Method: `fetchProductMeta(string $asin): array`
- Keep `downloadImage()` and `buildAffiliateUrl()` unchanged
- No fallback scraper — manual entry is the workflow until PA-API is live
- Add `AWS_ACCESS_KEY`, `AWS_SECRET_KEY`, `AWS_PARTNER_TAG` to `.env.example`

---

### Task 2: Multi-Program Affiliate Support

- Add `affiliate_provider VARCHAR(50) DEFAULT 'amazon'` to `amazon_products` table (migration 013)
- Rename table `amazon_products` → `products` (migration 013, data-preserving `RENAME TABLE`)
- Update all references in models, controllers, views, routes from `amazon_products` / `AmazonProduct` to `products` / `Product`
- In admin create/edit form: if provider != amazon, hide "Hämta från Amazon" button; show plain affiliate URL field
- Support providers: `amazon`, `adtraction`, `awin`, `biltema`, `xxl`, `campingse`, `other`

---

### Task 3: Gear Guide Page — `/utrustning`

- Add `featured_gear TINYINT(1) DEFAULT 0` column to `products` table (migration 014)
- Toggle in admin product edit view
- New public route `GET /utrustning` → `ProductController::gearGuide()` (or `AmazonController`)
- New view `views/public/gear-guide.php`
  - Sections by category: Kök & mat, Sovrum & komfort, Navigation & teknik, Utsida, Övrigt
  - Products filtered by `featured_gear = 1 AND is_published = 1`
  - Each product: image, title, short description, `/go/{slug}` link
- Add "Utrustning" to public nav and footer
- SEO: `<title>Frizze's utrustning — vad vi har i husbilen</title>`, structured data

---

### Task 4: Brevo Email Capture

**Note:** Requires a Brevo account and list ID.

- Add `BREVO_API_KEY` and `BREVO_LIST_ID` to `.env.example`
- New route `POST /nyhetsbrev/prenumerera` → `PublicController::subscribeNewsletter()`
- Controller: validate email, POST to `https://api.brevo.com/v3/contacts` with `listIds`
- No local DB storage — Brevo owns the list
- Form placement:
  1. Footer (all public pages) — inline email field + "Prenumerera" button
  2. `views/public/place-detail.php` — slide-in after 30s CSS transition (no JS library)
- Lead magnet: link to "Frizze's packlista" PDF in the confirmation email (set up in Brevo automation)
- Create the PDF packing list: export from the lists DB or format manually, store at `/public/packlista.pdf`

---

### Task 5: Admin Stats Dashboard — `/adm/statistik`

- New route `GET /adm/statistik` → `DashboardController::stats()` (or new `StatsController`)
- New view `views/dashboard/stats.php`
- Queries:
  ```sql
  -- Top places by views (last 30 days)
  SELECT name, slug, view_count FROM places
  WHERE public_allowed = 1 ORDER BY view_count DESC LIMIT 20;

  -- Top products by clicks (last 30 days)
  SELECT ap.title, ap.slug, COUNT(pc.id) as clicks
  FROM product_clicks pc
  JOIN amazon_products ap ON ap.id = pc.product_id
  WHERE pc.clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  GROUP BY ap.id ORDER BY clicks DESC LIMIT 20;

  -- Top referrers (which place pages drive shop clicks)
  SELECT referrer, COUNT(*) as clicks
  FROM product_clicks
  WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND referrer IS NOT NULL
  GROUP BY referrer ORDER BY clicks DESC LIMIT 20;
  ```
- Add "Statistik" link to admin sidebar in `views/partials/nav-desktop.php`

---

### Task 6: RSS Feed — `/rss.xml`

- New route `GET /rss.xml` → `PublicController::rss()`
- Query: published places ordered by most recent `ready_for_publish` visit date, limit 20
- Output format: RSS 2.0, `Content-Type: application/rss+xml; charset=utf-8`
- Each `<item>`: title, link (`/platser/{slug}`), description (first 200 chars of approved_public_text), pubDate, enclosure (thumbnail image URL)
- Add `<link rel="alternate" type="application/rss+xml">` to `views/layouts/public.php` `<head>`

---

## Dependencies Between Tasks

- Task 2 must complete before Task 3 (gear guide reads from renamed `products` table)
- Task 1 (PA-API) is independent — can run in parallel with 2-6
- Tasks 4, 5, 6 are fully independent

## Suggested Order

1. Task 6 (RSS — 1 hour, zero risk)
2. Task 5 (Stats dashboard — 2 hours, read-only)
3. Task 3 (Gear page — 2 hours, if enough products are tagged)
4. Task 4 (Brevo email — needs account setup first)
5. Task 2 (Multi-program — table rename is risky, do last)
6. Task 1 (PA-API — blocked on external approval)
