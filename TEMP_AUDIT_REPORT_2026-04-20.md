# Frizon kodbas-audit, 2026-04-20

Scope: lokal kodbas i `/Users/mattias/Development/frizon-web` plus lätt icke-invasiv prod-kontroll mot `https://app.frizon.org/`.

Begränsning: jag har inte läst någon `.env` eller testat autentiserade prod-flöden. Prod-kontrollen var endast GET/HEAD-liknande hämtningar och Lighthouse på startsidan.

## Sammanfattning

Övergripande status: kodbasen är bättre hårdnad än många små PHP-appar. CSRF finns på muterande adminflöden, PDO används konsekvent för dynamisk input, publicerade uploads serveras via `realpath`-kontroll, CSP finns med nonce och prod skickar HSTS/security headers.

Det jag skulle prioritera först är prestanda, inte akut säkerhet. Lighthouse på prod-startsidan gav Performance 49, Accessibility 94, Best Practices 77, SEO 92. Den stora externa boven i Lighthouse-körningen var Cloudflares `/cdn-cgi/challenge-platform/scripts/jsd/main.js`, som ser ut att komma från `app.frizon.org` eftersom den injiceras/proxyas under den egna hosten. Kodbasen hade också egna förbättringspunkter: `@import`-kedjad CSS, Leaflet laddades på startsidan direkt, och semantiska heading-nivåer hoppade från H1 till H3.

## Uppdatering efter Lighthouse-fixrunda

Följande har nu fixats i DEV:

- Startsidan har fått en H2-sektion för platslistan före platskortens H3-rubriker.
- Shopkortens "Läs mer"-länkar har fått produktspecifik tillgänglig länktext.
- Footer- och cookie-banner-kontrast har höjts.
- Publik layout laddar inte längre Leaflet CSS/JS render-blockande i `<head>`/footer.
- Startsideskartan lazy-loadar Leaflet när kartan närmar sig viewport.
- Platsdetaljkartan laddar Leaflet dynamiskt istället för via layouten.
- Startsideskartan använder lokal Leaflet MarkerCluster för att minska överlappande markörer.
- Publik layout använder `public/css/main.bundle.css`; käll-CSS är fortfarande modulär via `public/css/main.css`.
- `scripts/build-css-bundle.php` genererar CSS-bundlen.
- Service worker-cache är bumpad till `frizon-v14` och precachar nya bundle-/cluster-assets.
- Efter ny Lighthouse-runda: statiska assets har versionerade URL:er, origin-cache headers är satta till 1 år/immutable, CSS-bundlen är minifierad, MarkerCluster-CSS ligger i huvudbundlen i stället för separata requests, och OSM tiles använder Leaflets `detectRetina`.

Verifiering efter fixrundan:

- `php -l` över alla PHP-filer: OK.
- Befintlig testsvit i `tests/*.php`: OK.
- CSS-bundle genererad med `php scripts/build-css-bundle.php`.
- `node --check public/leaflet/leaflet.markercluster.js`: OK.

## Verifierat

- `php -l` över alla PHP-filer: OK.
- Befintlig testsvit i `tests/*.php`: OK, samtliga körda tester passerade.
- Lokal secrets-sökning: ingen `.env` eller nyckelfil hittades i repo, `.env` är ignorerad.
- Prod headers på `https://app.frizon.org/`: CSP, Referrer-Policy, X-Frame-Options, X-Content-Type-Options, Permissions-Policy och HSTS finns.
- Prod `robots.txt`: tillåter publik crawl, blockerar `/adm/`, pekar på `https://app.frizon.org/sitemap.xml`.
- Prod `sitemap.xml`: HTTP 200, XML content-type, 49 URL:er.
- Prod statiska assets: Cloudflare HIT och `Cache-Control: public, max-age=604800` för CSS/logo.

## Findings

### P1 - Performance: Cloudflare `/cdn-cgi` JavaScript detections dominerar Lighthouse

Källa: prod Lighthouse mot startsidan.

Lighthouse mätte:

- Performance: 49
- FCP: 2.9 s
- LCP: 4.7 s
- TBT: 1,570 ms
- CLS: 0
- JS execution: 3.5 s

Största raden var `https://app.frizon.org/cdn-cgi/challenge-platform/scripts/jsd/main.js` med ca 3,398 ms scripting. Det är troligen Cloudflare Bot Fight Mode / JavaScript detections / challenge platform, inte appens egen JS. URL:en ligger på `app.frizon.org` eftersom Cloudflare serverar den under den proxade domänen via den reserverade `/cdn-cgi/`-ytan. Det kan göra labbtesterna mycket sämre och kan påverka verkliga besökare om scriptet körs brett.

Rekommendation: kontrollera Cloudflare-inställningarna för `app.frizon.org` och se om JavaScript detections/challenge-platform kan undantas för vanliga publika sidvisningar om hotbilden tillåter det. Behåll skydd på `/adm/*`, formulär och högriskvägar. Mät om Lighthouse efter ändringen.

### P1 - Performance: CSS laddas via många `@import`

Kod: `public/css/main.css:18-48`.

`main.css` importerar 17 separata CSS-filer. I prod såg Lighthouse 25 stylesheet requests totalt. Cloudflare cache hjälper, men `@import` gör CSS-upptäckt mer sekventiell än en färdig bundle eller explicit preloadad kritisk CSS. Det är särskilt relevant när startsidan redan har karta, font och Cloudflare-script.

Status efter fixrunda: fixad i DEV med `public/css/main.bundle.css` och `scripts/build-css-bundle.php`. Publik layout laddar bundlen; källfilerna ligger kvar modulärt bakom `public/css/main.css`.

### P1 - Performance: Leaflet laddas direkt på publika startsidan

Kod: `views/layouts/public.php:49-52`, `views/layouts/public.php:114-115`, `views/public/homepage.php:50-59`, `views/public/homepage.php:150-180`.

Startsidan sätter `$useLeaflet = true`, vilket gör att Leaflet CSS och JS laddas i baslayouten. Leaflet JS är ca 147 KB okomprimerad lokalt och ca 42 KB transfer i Lighthouse. Kartan är relevant, men den behöver sannolikt inte blockera initial rendering.

Status efter fixrunda: fixad i DEV. Publik layout laddar inte längre Leaflet render-blockande; startsidan initierar kartan via `IntersectionObserver`, och platsdetaljen laddar Leaflet dynamiskt när sidan är redo.

### P2 - SEO/Accessibility: startsidan hoppar från H1 till H3

Kod: `views/public/homepage.php:11`, `views/public/homepage.php:87`, `views/partials/shop-card.php:30`.

Lighthouse flaggade heading order: efter H1 kommer platskort med H3 utan mellanliggande H2. Det är främst en tillgänglighets- och informationsarkitekturfråga, men tydligare sektioner hjälper också sökmotorer och AI-sammanfattare att förstå sidan.

Status efter fixrunda: fixad i DEV med en H2-sektion före karta/platslista. Shopkortens "Läs mer"-länkar har också fått produktspecifik tillgänglig länktext.

### P2 - SEO: sitemap använder dagens datum för statiska sidor och shop-index

Kod: `app/Controllers/PublicController.php:470-479`, `app/Controllers/PublicController.php:514-518`.

Sitemap sätter `lastmod` till `date('Y-m-d')` för `/`, `/topplista`, `/samarbeta` och `/shop` vid varje generering. Det signalerar dagliga ändringar även när sidans innehåll inte ändrats. Det är inte en katastrof, men kan minska trovärdigheten i sitemap-signalen.

Rekommendation: använd senaste relevanta `updated_at` från publicerade platser/produkter för startsida/topplista/shop, och en fast eller filbaserad ändringstid för kontaktsidan.

### P2 - Security: `style-src 'unsafe-inline'` krävs av mycket inline-style

Kod: `app/Helpers/security.php:147-159`, exempel inline-stil i `views/layouts/public.php:61-109`, `views/public/place-detail.php:153-165`.

CSP är bra och nonce används för script, men `style-src` innehåller `'unsafe-inline'` eftersom vyerna har mycket inline CSS och även inline `<style>` block. Det gör CSP mindre stark mot style-injection och gör det svårare att strama åt policyn.

Rekommendation: flytta successivt inline styles och inline `<style>` till CSS-filer. När publika vyer är rensade kan `style-src` gå mot `'self' https://fonts.googleapis.com` plus eventuellt nonce/hash för kvarvarande nödvändiga styles.

### P2 - Security hardening: admin-sessioner kan leva 7 dagar

Kod: `app/bootstrap.php` sätter 7 dagars session lifetime, `app/Services/Auth.php:26-31` regenererar session efter login.

För en privat app kan 7 dagar vara rimligt, men adminytan innehåller publicering, AI-anrop, bildhantering och Instagram-publicering. Lång sessionstid ökar skadan vid stulen cookie. Cookie-flaggorna är annars bra: HttpOnly, SameSite=Lax och Secure när HTTPS detekteras.

Rekommendation: korta adminsession till exempelvis 12-24 h, eller lägg till idle timeout i sessionen. För extra hårdning: kräv lösenord igen för Instagram-publicering, lösenordsbyte och eventuella framtida destruktiva massåtgärder.

### P2 - Security: proxy trust default kan feltolka spoofad `X-Forwarded-Proto`

Kod: `app/Helpers/security.php:15-24`, `.env.example:4-5`, `scripts/check-production-config.php:52-54`.

Om `ENFORCE_TRUSTED_PROXIES=false` returnerar `app_is_https_request()` true när klienten skickar `X-Forwarded-Proto: https`. I prod bakom Cloudflare verkar HSTS och Secure fungera, men defaulten är svag om appen någonsin exponeras direkt mot internet via HTTP.

Rekommendation: sätt `ENFORCE_TRUSTED_PROXIES=true` i prod och konfigurera Cloudflare/LiteSpeed proxy-IP-intervall eller närmaste reverse proxy. Lägg gärna in detta som hard fail i production preflight om prod alltid ligger bakom proxy.

### P2 - Security/Robustness: Amazon image download saknar verifiering av faktisk bilddimension före GD-dekodning

Kod: `app/Services/AmazonFetcher.php:153-194`, `app/Services/AmazonFetcher.php:202-230`.

Nedladdade Amazon-bilder begränsas till 10 MB, domän och Content-Type. Därefter körs `imagecreatefromstring()` utan dimension/pixelgräns motsvarande `ImageService::isWithinImageLimits()` för vanliga uploads (`app/Services/ImageService.php:113-127`). 10 MB kan fortfarande vara en stor/dekompressionsdyr bild.

Rekommendation: använd `getimagesizefromstring()` på `$rawData`, avvisa extrema dimensioner/pixlar, och återanvänd samma limits som vanliga uploads innan GD försöker skapa bitmap.

### P2 - AI/Operations: hårdkodade Anthropic-modellnamn bör verifieras i prod

Kod: `app/Services/AiService.php:27-29`, `scripts/check-production-config.php:42-49`.

`ClaudeAiProvider` använder `claude-opus-4-6` och `claude-sonnet-4-6`. Testsviten kör fake provider och fångar därför inte om produktionsmodellerna är fel, avvecklade eller saknar åtkomst. Om API:t börjar svara fel påverkas publicering/SEO/AI-funktioner.

Rekommendation: lägg modellnamn i `.env`, verifiera med en liten smoke-test i `check-production-config.php` när `AI_PROVIDER=claude`, eller skapa en separat manuell preflight för AI.

### P3 - SEO/GEO: `llms.txt` är bra men har språkmix och begränsad struktur

Kod: `app/Controllers/PublicController.php:533-608`.

`/llms.txt` finns och är ett plus. Texten är dock huvudsakligen engelska medan sajten är svensk, och den listar endast platsers meta descriptions och de 50 senaste recensionerna. För GEO/AI-citation kan den bli mer användbar om den tydligare grupperar ämnen, källstatus och viktigaste sidor.

Rekommendation: skriv `llms.txt` konsekvent på svenska eller tvåspråkigt med tydliga rubriker, lägg till "canonical key pages", kontakt/varumärkesentity, och korta faktarader om hur recensioner samlas in.

### P3 - Security/Setup: seed-kommentar innehåller känt exempel-lösenord

Kod: `database/seed.sql:1-6`.

Hasharna är placeholders, så detta är inte en aktiv hemlighet. Men kommentaren anger `frizon2026` som seed-lösenord. Det är lätt att någon vid framtida setup följer kommentaren och råkar använda ett känt lösenord.

Rekommendation: ändra kommentaren till att explicit säga att inget standardlösenord ska användas, och generera ett unikt lösenord per användare.

## Positiva observationer

- Muterande adminroutes har konsekvent `Auth::requireLogin()` och CSRF-kontroll enligt controller-scan.
- PDO prepared statements används för filter och parametrar i de granskade modellerna.
- Uploads sparas utanför `public/` och serveras via kontrollerad `/uploads/{variant}/{filename}`-väg med `realpath`.
- Vanliga bild-uppladdningar har MIME-, storleks-, dimension- och pixelgränser.
- Kontaktformuläret har honeypot, tidsbaserad sessionstoken och IP-rate-limit.
- Affiliate redirect är inte öppen redirect i normalflödet eftersom `affiliate_url` byggs från validerad Amazon-URL vid admin-create/update.
- Publika views använder i huvudsak `htmlspecialchars()` och JS-popups bygger DOM med `textContent` för kartdata.
- Prod har fungerande cache på statiska assets via Cloudflare.

## Snabba åtgärdsförslag

1. Kontrollera Cloudflare challenge/Bot-inställningar för publika sidor och mät om Lighthouse.
2. Mät om Lighthouse efter DEV-fixarna är deployade.
3. Kontrollera att Leaflet MarkerCluster känns bra visuellt på mobil och desktop.
4. Överväg nästa steg: separera publik CSS från admin-CSS för ännu mindre `main.bundle.css`.
5. Byt sitemap `lastmod` för statiska/index-sidor till verklig senaste ändring.
6. Flytta inline styles från publika templates till CSS så CSP kan stramas åt senare.
7. Lägg pixel/dimensionsgräns även på fjärrnedladdade Amazon-bilder.
8. Flytta Anthropic-modellnamn till env och smoke-testa AI-provider.

## Kommandon körda

```bash
php scripts/build-css-bundle.php
node --check public/leaflet/leaflet.markercluster.js
for f in $(rg --files -g '*.php'); do php -l "$f" >/dev/null || exit 1; done
for f in tests/*.php; do php "$f" || exit 1; done
curl -sS -D - -o /tmp/frizon-home.html https://app.frizon.org/
curl -sS https://app.frizon.org/robots.txt
curl -sS -D - -o /tmp/frizon-sitemap.xml https://app.frizon.org/sitemap.xml
curl -sS -D - -o /tmp/frizon-llms.txt https://app.frizon.org/llms.txt
npx --yes lighthouse https://app.frizon.org --quiet --chrome-flags="--headless" --output=json --output-path=/tmp/frizon-lighthouse.json --only-categories=performance,accessibility,best-practices,seo
```
