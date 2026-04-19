# Platshantering: Separation plats vs besök + besöksredigering

**Datum:** 2026-04-19
**Status:** Draft

## Problem

1. Publicerad besökstext (`approved_public_text`) kan inte redigeras direkt — måste köra om AI-generering
2. AI-prompter använder tredje person ("Mattias och Ulrica") istället för jag/vi-form
3. Publika platssidan saknar tydlig separation mellan plats och besök vid upprepade besök
4. Ingen publik besökssida — bilder och detaljer visas bara inline på platssidan

## Scope

Bygger vidare på befintlig datamodell (inga schemaändringar). Fem arbetsområden:

### 1. Redigerbar besökstext i admin

**Nuläge:** `VisitController::update()` (rad 151-187) uppdaterar alla besöksfält utom `approved_public_text`. Edit-vyn (`views/visits/edit.php`) saknar fält för publicerad text.

**Ändring:**
- Lägg till `approved_public_text`-textarea i `views/visits/edit.php` — med grön ram om publicerad, grå om ej publicerad
- Lägg till `approved_public_text` i `VisitController::update()` i update-arrayen
- Två separata knappar: "Spara text" (sparar formuläret inkl. textändringen) och "Brodera om" (befintligt AI-flöde, oförändrat)
- Hjälptext under fältet: "Redigera direkt ovan. 'Brodera om' genererar ny AI-text."

### 2. AI-prompter: jag/vi-form

**Nuläge:** `AiService::sw()` (rad 129-133) instruerar AI:n: "Om du nämner personnamn får du ENDAST använda Mattias och Ulrica". Hårdkodade tredjepersonstexter finns i:
- `AiService::sw()` rad 132 — prompt-instruktion
- `AiService::generatePlaceSeo()` rad 443 — meta-description: "Recenserad av Mattias och Ulrica..."
- `AiService` rad 401 — fallback-text: "som vi besökte"

**Ändring:**
- `sw()`: Byt till "Skriv alltid i jag- eller vi-form. Använd aldrig tredje person eller personnamn som 'Mattias och Ulrica'. Exempel: 'Vi stannade här...' inte 'Mattias och Ulrica stannade här...'"
- `generatePlaceSeo()` rad 443: Byt meta-description till jag/vi-form, t.ex. "Vi har besökt {name} — en {type}{country}. Läs vår recension ur ett husbilsperspektiv."
- Kontrollera övriga hårdkodade texter i AiService och fixa alla tredjepersonsreferenser

**Undantag:** Landningssidan och /samarbeta-sidan får behålla "Mattias och Ulrica" — dessa ligger utanför AiService.

### 3. Publik besökssida (ny route)

**Nuläge:** Ingen publik vy för enskilt besök. Besök visas bara som block på platssidan.

**Ny route:** `GET /platser/{slug}/besok/{id}` → `PublicController::visitDetail()`

**Innehåll (mobile-first, uppifrån och ned):**
- Tillbaka-länk till platssidan
- Rubrik: besöksdatum + platsnamn
- Bildgrid: huvudbild full bredd, övriga i 2-kolumnsgrid (ej sidoscroll)
- Besökstext (`approved_public_text`)
- Betyg i 2x2-grid + snittbetyg
- Detaljer: prisnivå, "skulle återvända", "passar för"
- Länk tillbaka: "Alla besök på {platsnamn}"

**Villkor:** Bara publicerade besök (`ready_for_publish = 1`) på publika platser (`public_allowed = 1`).

### 4. Publik platssida — besökspresentation

**Nuläge:** `views/public/place-detail.php` visar alla publicerade besök som jämställda block med inline-bilder.

**Ändring:**
- Platsbild (optional) — om `places.preview_image_id` är satt, visa bilden högst upp under kartan. Väljs i admin från valfri besöksbild.
- "Om platsen" — `default_public_text` — visas separat ovanför besök (redan så, behåll)
- "Senaste besöket" — senaste publicerade besöket som kompakt kort (datum, betyg, trunkerad text, "N bilder", chevron `›`). Klickbart → `/platser/{slug}/besok/{id}`
- "Tidigare besök (N)" — `<details>`-element, ihopfällt som default. Inuti: kompakta kort per besök i fallande datumordning. Varje kort klickbart → besökssida
- Visa "Besökt N gånger" i platshuvudet

### 5. Admin platssida — besökslista

**Nuläge:** `views/places/show.php` listar besök redan i fallande ordning med trunkerade noteringar.

**Ändring:**
- Behåll platt lista (ingen ihopfällning)
- Lägg till publiceringsstatus: grön vänsterkant + "Pub" för publicerade, grå + "Ej pub" för opublicerade
- Klick på besökskort → `/adm/besok/{id}` (show-vyn, redan existerande)
- Pennikon/chevron som visuell indikator att kortet är klickbart

## Berörda filer

| Fil | Ändring |
|-----|---------|
| `app/Controllers/VisitController.php` | Lägg till `approved_public_text` i `update()` |
| `app/Controllers/PublicController.php` | Ny metod `visitDetail()` |
| `app/Services/AiService.php` | Jag/vi-prompter, fixa tredjepersonstexter |
| `views/visits/edit.php` | Lägg till redigerbart `approved_public_text`-fält |
| `views/public/place-detail.php` | Besökskort med ihopfällning, ta bort inline-bilder |
| `views/public/visit-detail.php` | Ny vy — publik besökssida |
| `views/places/show.php` | Publiceringsstatus + klickbarhet på besökskort + "Använd som platsbild"-knapp på besöksbilder |
| `routes/web.php` | Ny route: `GET /platser/{slug}/besok/{id}` |
| `app/Controllers/PlaceController.php` | Ny metod `setPreviewImage()` |
| `database/migrations/NNN_add_preview_image_to_places.sql` | Ny kolumn `preview_image_id` |

## Datamodell

En schemaändring:
- **Ny kolumn:** `places.preview_image_id` (INT UNSIGNED, NULL, FK till `visit_images.id` ON DELETE SET NULL) — optional omslagsbild för platsen, vald från besöksbilder
- Migration: `database/migrations/NNN_add_preview_image_to_places.sql`

Befintliga fält (oförändrade):
- `visits.approved_public_text` — redan i tabellen, bara inte redigerbar via formuläret
- `visits.ready_for_publish` — redan flagga för publiceringsstatus
- `places.default_public_text` — platsbeskrivning, oförändrad

## Avgränsningar

- Landningssidan och /samarbeta behåller "Mattias och Ulrica" (ej AI-genererat)
- Ingen aggregering av betyg över besök — varje besök behåller sitt eget snitt
- Inga nya JavaScript-beroenden — `<details>`-elementet är native HTML
- Service worker cache-bump krävs om CSS/JS ändras
