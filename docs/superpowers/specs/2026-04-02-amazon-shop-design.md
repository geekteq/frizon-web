# Amazon Shop — Design Spec
**Datum:** 2026-04-02
**Status:** Godkänd

---

## Översikt

En kuraterad produktrekommendationssida på `frizon.org/shop` som visar saker Mattias och Ulrica faktiskt använder och gillar. Produkterna länkas till Amazon.se via affiliate-länkar. Sidan integreras fullt ut i den befintliga PHP/MariaDB-appen och följer appens befintliga arkitekturmönster.

---

## Datamodell

### Tabell: `amazon_products`

```sql
CREATE TABLE amazon_products (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug               VARCHAR(255) NOT NULL UNIQUE,
    title              VARCHAR(255) NOT NULL,
    amazon_url         VARCHAR(2048) NOT NULL,
    affiliate_url      VARCHAR(2048) NOT NULL,
    image_path         VARCHAR(512),
    amazon_description TEXT,
    our_description    TEXT,
    seo_title          VARCHAR(255),
    seo_description    VARCHAR(320),
    category           VARCHAR(100),
    sort_order         SMALLINT UNSIGNED DEFAULT 0,
    is_featured        TINYINT(1) DEFAULT 0,
    is_published       TINYINT(1) DEFAULT 0,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Konfiguration

`AMAZON_ASSOCIATE_ID` läggs i `.env`. Affiliate-URL genereras automatiskt genom att lägga till/ersätta `tag`-parametern i den klistrade Amazon-URL:en.

---

## Auto-hämtning vid sparande

När ett produktformulär sparas (create eller update) triggas automatiskt:

1. **Bild:** cURL mot `amazon_url`, extrahera `og:image`, ladda ner och spara lokalt i `storage/uploads/amazon/`. Sparas som `image_path`.
2. **Beskrivning:** Extrahera `og:description`. Om texten inte är på svenska → översätt via `AiService` (Claude) till korrekt svenska innan sparande i `amazon_description`.
3. **Affiliate-URL:** `amazon_url` + `?tag=AMAZON_ASSOCIATE_ID` (befintlig tag-parameter ersätts om den finns).
4. **SEO-fält:** Claude genererar `seo_title` och `seo_description` — säljande, på svenska, optimerade för direktlänkar — baserat på titel + beskrivning. Sparas i `seo_title`/`seo_description`. Båda fälten är redigerbara i admin om man vill justera manuellt.

---

## Routing

### Publikt (ingen auth)

| Method | Path | Controller::action |
|--------|------|--------------------|
| GET | `/shop` | `AmazonController::index` |
| GET | `/shop/{slug}` | `AmazonController::show` |

### Admin (bakom auth)

| Method | Path | Controller::action |
|--------|------|--------------------|
| GET | `/adm/amazon-lista` | `AmazonController::adminIndex` |
| GET | `/adm/amazon-lista/ny` | `AmazonController::adminCreate` |
| POST | `/adm/amazon-lista` | `AmazonController::adminStore` |
| GET | `/adm/amazon-lista/{id}/redigera` | `AmazonController::adminEdit` |
| PUT | `/adm/amazon-lista/{id}` | `AmazonController::adminUpdate` |
| DELETE | `/adm/amazon-lista/{id}` | `AmazonController::adminDestroy` |
| POST | `/adm/amazon-lista/{id}/ai/generera` | `AmazonController::generateDraft` |
| POST | `/adm/amazon-lista/{id}/ai/{draftId}/godkann` | `AmazonController::approveDraft` |
| POST | `/adm/amazon-lista/{id}/ai/{draftId}/avvisa` | `AmazonController::rejectDraft` |

---

## Navigation

### Publik header (`views/layouts/public.php`)

```
[Platser]  [Shop]     [LOGO]     [Topplista]
```

Vänster om logon: Platser + Shop. Höger: Topplista (oförändrad).

### Admin-sidebar (`views/partials/nav-desktop.php`)

Ny länk under sektionen "Publikt", under "Publicera":
- **Shop** → `/adm/amazon-lista`

### Mobil bottenbar (`views/partials/nav-mobile.php`)

Ingen förändring — admin-mobilnavigationen lämnas orörd (den är redan full).

---

## Views

### `views/public/shop.php`

- H1 + ingress (hårdkodad text i controller, lätt att ändra)
- Diskret affiliate-disclaimer: *"Vi kan tjäna provision på köp via våra länkar — vi rekommenderar bara saker vi själva använder och gillar."*
- Featured-sektion överst (om produkter är markerade featured)
- Kategorifilter som chips (samma stil som platsfilter på hemsidan)
- Produktkort i responsivt grid: bild, titel, kategori, vår beskrivning (trunkerad), knapp "Se hos Amazon →"
- Enkel client-side textsökning på titel
- SEO: `seo_title` som `<title>`, `seo_description` som `<meta name="description">`

### `views/public/shop-product.php`

- Fullständig produktsida med vår beskrivning, Amazon-beskrivning, bild
- Tydlig CTA-knapp "Köp hos Amazon →" (affiliate-länk)
- SEO: produktens `seo_title` och `seo_description`
- Strukturerad data (JSON-LD Product schema)
- Canonical URL

### `views/amazon/index.php`

- Tabell med alla produkter (publicerade + opublicerade)
- Inline-toggle för published/featured
- Länk till redigera, knapp för radera

### `views/amazon/create.php` / `views/amazon/edit.php`

- Fält: titel, amazon_url, kategori (med autocomplete), sort_order, is_featured, is_published
- Readonly-fält: amazon_description (fylls automatiskt vid sparande)
- Textarea: our_description + "Brodera ut"-knapp (identiskt AI-draft-flöde som besöks-vyn)
- Kategori-autocomplete: JSON-endpoint `/adm/api/amazon/kategorier` returnerar befintliga kategorier — samma mönster som `suitable_for` i VisitController
- Readonly-fält: seo_title, seo_description (auto-genererade, manuellt redigerbara)
- Förhandsgranskning av hämtad produktbild

---

## Hemsidan — ny sektion

`views/public/homepage.php` får en ny sektion längst ned:

- Rubrik: "Nytt i shoppen"
- Max 3 senast publicerade produkter som horisontella kort
- Länk "Se alla produkter →" till `/shop`

---

## Sitemap

`PublicController::sitemap` utökas med:
- `/shop` (statisk URL)
- En URL per publicerad produkt: `/shop/{slug}`

---

## SEO-krav

- Varje `/shop/{slug}` har unik `<title>` och `<meta description>` (från `seo_title`/`seo_description`)
- Semantisk HTML med korrekt H1/H2-hierarki
- JSON-LD Product-schema på produktdetaljsidor
- Canonical-tag på alla sidor
- All text på korrekt svenska (Amazon-texter översätts vid sparande om de är på annat språk)

---

## Säkerhet

- All output escapas med `htmlspecialchars()`
- CSRF-skydd på alla POST/PUT/DELETE-formulär (befintligt `csrf-field.php`-partial)
- URL-validering på `amazon_url` (måste vara amazon.se-domän)
- Auth-middleware på alla `/adm/amazon-lista`-routes (befintlig `AuthMiddleware`)
- cURL-hämtning körs server-side med timeout, ingen användarinput når shell

---

## Filer att skapa/ändra

### Nya filer
- `database/migrations/xxx_create_amazon_products.sql`
- `app/Models/AmazonProduct.php`
- `app/Controllers/AmazonController.php`
- `app/Services/AmazonFetcher.php` (cURL-hämtning av bild + beskrivning)
- `views/public/shop.php`
- `views/public/shop-product.php`
- `views/amazon/index.php`
- `views/amazon/create.php`
- `views/amazon/edit.php`

### Ändrade filer
- `routes/web.php` — lägg till alla nya routes (inkl. `/adm/api/amazon/kategorier`)
- `views/layouts/public.php` — lägg till "Shop" i navbaren
- `views/partials/nav-desktop.php` — lägg till "Shop" under "Publikt"
- `views/public/homepage.php` — lägg till "Nytt i shoppen"-sektion
- `app/Controllers/PublicController.php` — uppdatera sitemap
- `.env.example` — lägg till `AMAZON_ASSOCIATE_ID`

---

## Exempeldata

6 produkter med varierade kategorier (Kök, Elektronik, Säkerhet, Navigation, Sovkomfort, Utrustning) — alla publicerade, 2 markerade featured.
