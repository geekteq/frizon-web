# Platsbeskrivningar och besöksprodukter — design

**Datum:** 2026-05-03
**Status:** Designförslag

## Bakgrund

Två relaterade problem i nuvarande modell:

1. **Platsbeskrivningen skrivs över av besök.** När ett AI-utkast godkänns på ett besök kopieras texten både till `visits.approved_public_text` och `places.default_public_text`. Det innebär att den platsbeskrivning som skrevs när platsen lades till försvinner vid första besöket — och varje nytt besök skriver över föregående. Roten finns i `app/Controllers/AiController.php:277-282`.

2. **Produkter sitter på fel nivå.** `place_products` kopplar Amazon-produkter direkt till platsen. I praktiken används olika utrustning vid olika besök (t.ex. Petromax Atago en gång, Cadac Safari Chef en annan). Modellen klarar inte att representera detta.

## Mål

- Platsbeskrivning och besöksbeskrivning är separata och uppdateras oberoende.
- AI kan föreslå en uppdaterad platsbeskrivning baserat på publicerade besök, men endast efter manuellt godkännande.
- Produkter kan kopplas både per besök ("vad vi använde") och per plats ("rekommenderat för platsen").
- Befintliga data rörs ej.

## Konceptuell modell

| Fält | Vad det är | Vem fyller i | Skrivs över? |
|---|---|---|---|
| `places.default_public_text` | Sammanfattning av platsen, växer över tid | Människa, ev. via AI-förslag som godkänns | Bara via medvetet godkännande på plats-edit |
| `visits.approved_public_text` | Recension av ett specifikt besök | Människa, ev. via AI från `raw_note`+ratings | Aldrig från andra besök |

**Princip:** AI är ett verktyg som föreslår — människan godkänner. Inget skrivs automatiskt över.

## Ändringar

### 1. Ta bort auto-överskrivning av platsbeskrivning

**Fil:** `app/Controllers/AiController.php`, rad 277-282 (i `approveDraft`)

Ta bort hela blocket som uppdaterar `places.default_public_text` när ett besöksutkast godkänns. Godkänt besöksutkast skriver enbart till `visits.approved_public_text`.

SEO-regenereringen som följer (rad 284 och framåt) behålls — den beror på besökets text, inte platsens.

### 2. Utvidga `ai_drafts` — polymorf target

`ai_drafts` är idag bundet till `visit_id`. Vi behöver kunna lagra utkast även för platser.

**Migration `019_ai_drafts_target.sql`:**

```sql
ALTER TABLE ai_drafts
    ADD COLUMN target_type ENUM('visit','place') NOT NULL DEFAULT 'visit' AFTER id,
    ADD COLUMN target_id INT UNSIGNED NULL AFTER target_type;

UPDATE ai_drafts SET target_id = visit_id WHERE target_id IS NULL;

ALTER TABLE ai_drafts
    MODIFY target_id INT UNSIGNED NOT NULL,
    DROP FOREIGN KEY ai_drafts_ibfk_1,
    MODIFY visit_id INT UNSIGNED NULL,
    ADD INDEX idx_ai_drafts_target (target_type, target_id);
```

(FK-namnet `ai_drafts_ibfk_1` verifieras innan migration körs.)

`visit_id` behålls nullable för bakåtkompatibilitet och för att läsmodeller fortfarande kan filtrera på det. Ny kod skriver alltid både `target_type`/`target_id` och, om `target_type='visit'`, även `visit_id`.

**Modell:** `app/Models/AiDraft.php` får metoder `findByTarget(string $type, int $id)` och `createForTarget(string $type, int $id, ...)`.

### 3. Ny "Brodera ut text (AI)" på plats-edit

**Endpoint:** `POST /adm/plats/{id}/ai/genera`

**Indata till AI-prompten:**
- `places.default_public_text` (originalbeskrivningen — kallas "platsens nuvarande beskrivning" i prompten)
- Alla `visits.approved_public_text` från besök på platsen där `ready_for_publish = 1` (kronologiskt sorterade)
- `places.name`, `places.place_type`, `places.address_text` som kontext

**Indata som *inte* används:** `raw_note`, `plus_notes`, `minus_notes`, `tips_notes`, ratings, ej publicerade besök.

**Flöde:**
1. Användare klickar "Brodera ut text" på `/adm/plats/{id}/redigera`
2. AI-utkast skapas i `ai_drafts` med `target_type='place'`, `target_id={place_id}`
3. Utkastet visas på samma sida med "Godkänn" / "Avslå" / "Generera nytt"
4. Vid godkännande: `places.default_public_text` uppdateras, `ai_drafts.status='approved'`

**UI:** Återanvänd existerande AI-utkast-komponent från besök-edit (knapp + textarea + godkänn-flöde) men kopplad till plats-endpoints.

**Endpoints (parallella med besöksflödet):**
- `POST /adm/plats/{id}/ai/genera` — skapa utkast
- `POST /adm/plats/{id}/ai/{draftId}/godkann` — godkänn → skriv `default_public_text`
- `POST /adm/plats/{id}/ai/{draftId}/avsla` — markera avslagen

`AiController` får motsvarande metoder (`generateForPlace`, `approvePlaceDraft`, `rejectPlaceDraft`) som speglar besöksvarianterna.

### 4. Ny tabell `visit_products`

**Migration `018_visit_products.sql`:**

```sql
CREATE TABLE IF NOT EXISTS visit_products (
    visit_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    note        VARCHAR(255) DEFAULT NULL,
    sort_order  TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (visit_id, product_id),
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES amazon_products(id) ON DELETE CASCADE,
    INDEX idx_visit_products_visit (visit_id),
    INDEX idx_visit_products_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Spegling av `place_products` men kopplad till besök. Befintliga `place_products` rörs ej.

### 5. Admin-UI

**Plats-edit (`views/places/edit.php`):**
- Befintlig produktväljare behålls — nu med rubrik "Rekommenderat för platsen" eller liknande som tydliggör syftet (kuraterade tips).
- Ny knapp "Brodera ut text (AI)" intill `default_public_text`-fältet, kopplad till plats-AI-flödet.

**Besök-edit (`views/visits/edit.php`):**
- Ny sektion "Produkter vi använde" med samma produktväljarkomponent som plats-edit, kopplad till `visit_products`.

**Återanvändning:** Produktväljaren bör extraheras till en partial (`views/partials/product-picker.php`) om den inte redan är det, så samma komponent kan återanvändas på båda sidor med olika `target_type`/`target_id`.

### 6. Publik plats-sida (`views/public/place.php` via `PublicController::showPlace`)

Renderingsregler:

**Sektion "Rekommenderat för platsen"** (`place_products`):
- Visas om `place.public_allowed = 1` och listan är icke-tom
- Inget besökskrav

**Sektion "Vid våra besök"** (`visit_products` per besök):
- Visas om det finns ≥1 publicerat besök (`visits.ready_for_publish = 1`) på platsen och något av dem har `visit_products`
- Renderas som kronologisk lista: per besök visa datum + recensionstext + de produkter som användes vid just det besöket

Om en plats är publik men saknar besök visas bara "Rekommenderat för platsen" + `default_public_text`.

### 7. SEO/strukturerad data

`PublicController::showPlace` använder idag `place.default_public_text` för meta-description och Place-schema (rad 178, 200-201). Det fortsätter fungera oförändrat — `default_public_text` är fortfarande den auktoritativa platsbeskrivningen.

Visit-recensioner (rad 224-) fortsätter byggas från `visits.approved_public_text` per besök.

## Befintlig data

- **`place_products`-rader:** Lämnas orört. Visas som "Rekommenderat för platsen" på publika sidor.
- **`places.default_public_text`-rader som överskrivits av besök tidigare:** Vi gör ingen återställning. Användaren kan köra "Brodera ut text" på dessa platser för att få en ny sammanfattning, eller redigera manuellt.
- **`ai_drafts`-rader:** Backfillas till `target_type='visit'` via migration.

## Tester

Lättviktiga validerings-/testskript (i linje med `CLAUDE.md`):

- Plats-AI-flöde: skapa plats med beskrivning, lägg till publicerat besök, kör generera → utkast skapas med rätt `target_type`/`target_id` → godkänn → `default_public_text` uppdateras
- Bekräfta att godkännande av besöksutkast *inte* längre rör `places.default_public_text`
- `visit_products` CRUD: lägg till, ta bort, sortera per besök
- Publik rendering: plats utan besök men med `place_products` → bara "Rekommenderat" syns; plats med besök som har `visit_products` → båda sektionerna syns

## Filer som påverkas

**Nya:**
- `database/migrations/018_visit_products.sql`
- `database/migrations/019_ai_drafts_target.sql`
- `views/partials/product-picker.php` (om den inte redan finns som komponent)

**Ändras:**
- `app/Controllers/AiController.php` — ta bort auto-överskrivning, lägg till plats-AI-metoder
- `app/Models/AiDraft.php` — target-baserade hjälpmetoder
- `app/Controllers/PlaceController.php` — koppla in plats-AI på edit-vyn
- `app/Controllers/VisitController.php` — hantera `visit_products` vid spara
- `app/Controllers/PublicController.php` — uppdatera platssidans rendering
- `app/Models/Place.php` / `app/Models/Visit.php` — ev. helpers för produktlistor
- `routes/` — nya route-rader för plats-AI
- `views/places/edit.php` — AI-knapp + tydligare produktväljare-rubrik
- `views/visits/edit.php` — ny produktväljare-sektion
- `views/public/place.php` — uppdaterade sektioner

**Orörda:**
- `database/migrations/010_place_products.sql` — tabellen behålls oförändrad

## Öppna mindre detaljer (fastställs vid implementation)

- Exakt FK-konstraintnamn i `019_ai_drafts_target.sql` — verifieras med `SHOW CREATE TABLE ai_drafts` innan migration körs
- Namn på "Rekommenderat för platsen"-rubriken i UI (kopiering kan justeras under implementation)
