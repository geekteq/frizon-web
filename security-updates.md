# Security Updates

## 2026-04-09 — Batch 1

### Ändringar
- Tätade stored XSS-risken i adminstatistiken genom att inte längre göra okontrollerade referrers klickbara.
- Lade till strikt allowlist för externa referrer-URL:er: endast `http` och `https` får renderas som länkar.
- Lade till säkrare `rel`-attribut på externa adminlänkar som öppnas i ny flik.
- Bytte råa providerfel i AI- och Instagramflöden mot generiska användarfel och intern loggning.
- Tog bort skrivning av Instagram refresh-token till `.env`.
- Införde separat runtime-lagring för uppdaterad Instagram-token i `storage/runtime-secrets/instagram-token.json`.
- Gitignorerade runtime-secrets och skapade `.gitkeep` för katalogen.

### Påverkade filer
- [app/Controllers/DashboardController.php](/Users/mattias/Development/frizon-web/app/Controllers/DashboardController.php)
- [views/dashboard/stats.php](/Users/mattias/Development/frizon-web/views/dashboard/stats.php)
- [app/Controllers/AiController.php](/Users/mattias/Development/frizon-web/app/Controllers/AiController.php)
- [app/Controllers/VisitController.php](/Users/mattias/Development/frizon-web/app/Controllers/VisitController.php)
- [app/Controllers/PublishController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublishController.php)
- [app/Controllers/AmazonController.php](/Users/mattias/Development/frizon-web/app/Controllers/AmazonController.php)
- [app/Services/InstagramService.php](/Users/mattias/Development/frizon-web/app/Services/InstagramService.php)
- [/.gitignore](/Users/mattias/Development/frizon-web/.gitignore)

### Motiv
- Minska risken för admin-XSS även om systemet används av ett litet antal betrodda användare.
- Minska informationsläckage från tredjepartsintegrationer till klient/UI.
- Separera runtime-hemligheter från statisk applikationskonfiguration.

## 2026-04-09 — Batch 2

### Ändringar
- Införde `APP_ENV`-default i bootstrap så driftbeteende kan skilja på development och production.
- Slutade falla tillbaka tyst till `FakeRouteProvider` i produktion.
- Gjorde ruttberäkning atomärare: gamla segment tas inte bort förrän nya ruttsegment faktiskt har beräknats.
- Lade till säker felhantering och intern loggning för ruttberäkning.
- Gjorde kontaktformens spam-token sessionbunden, slumpmässig, single-use och tidsbegränsad.
- Tog bort beroendet av `APP_KEY` för kontaktformens antispam-token.

### Påverkade filer
- [app/bootstrap.php](/Users/mattias/Development/frizon-web/app/bootstrap.php)
- [app/Services/Routing/OpenRouteServiceProvider.php](/Users/mattias/Development/frizon-web/app/Services/Routing/OpenRouteServiceProvider.php)
- [app/Controllers/TripController.php](/Users/mattias/Development/frizon-web/app/Controllers/TripController.php)
- [app/Controllers/PublicController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublicController.php)

### Motiv
- Förhindra att produktionsmiljön ger falska ruttdata utan tydligt fel.
- Minska risken för replay av kontaktformuläret och ogiltiga/cachade formulärposter.
- Skydda befintliga ruttsegment från att raderas om extern ruttjänst fallerar mitt i beräkningen.

## 2026-04-09 — Batch 3

### Ändringar
- Härdade inloggning med tre parallella throttles i stället för bara en:
  - per användarnamn + IP
  - per användarnamn över alla IP-adresser
  - per IP-adress över alla användarnamn
- Skärpte lösenordsbyte så nya lösenord måste vara minst 12 tecken och innehålla både bokstäver och siffror.
- Lade till dimensions- och pixelgränser för bilduppladdning innan någon tung bilddekodning eller resampling sker.
- Exponerade nya uppladdningsgränser i applikationskonfiguration och `.env.example`.

### Påverkade filer
- [app/Controllers/AuthController.php](/Users/mattias/Development/frizon-web/app/Controllers/AuthController.php)
- [app/Services/ImageService.php](/Users/mattias/Development/frizon-web/app/Services/ImageService.php)
- [config/app.php](/Users/mattias/Development/frizon-web/config/app.php)
- [/.env.example](/Users/mattias/Development/frizon-web/.env.example)

### Motiv
- Minska risken för brute force och credential stuffing även i en liten, privat adminmiljö.
- Minska konsekvensen av svaga eller återanvända lösenord.
- Förhindra att små men extremt högupplösta bilder orsakar onödig minnesförbrukning eller processkrascher i GD.

## 2026-04-09 — Batch 4

### Ändringar
- Lade till ett separat `ActionRateLimiter`-lager för dyra, autentiserade adminåtgärder.
- Aktiverade rate limiting per användare för:
  - AI-utkast för besök och platser
  - AI-bildtexter
  - Instagram-publicering
  - bilduppladdning
- Amazon-refetch
- Amazon AI-utkast
- ruttberäkning
- Lade till stöd för `TRUSTED_PROXIES` i miljökonfigurationen.
- Gjorde strict proxy-trust opt-in via `ENFORCE_TRUSTED_PROXIES=false` som standard för att inte bryta befintlig hem/VPN-access bakom proxy.
- Gav JSON-LD-skript samma CSP-nonce som övriga tillåtna inline-skript i den publika layouten.

### Påverkade filer
- [app/Services/ActionRateLimiter.php](/Users/mattias/Development/frizon-web/app/Services/ActionRateLimiter.php)
- [app/Controllers/AiController.php](/Users/mattias/Development/frizon-web/app/Controllers/AiController.php)
- [app/Controllers/VisitController.php](/Users/mattias/Development/frizon-web/app/Controllers/VisitController.php)
- [app/Controllers/TripController.php](/Users/mattias/Development/frizon-web/app/Controllers/TripController.php)
- [app/Controllers/AmazonController.php](/Users/mattias/Development/frizon-web/app/Controllers/AmazonController.php)
- [app/Helpers/security.php](/Users/mattias/Development/frizon-web/app/Helpers/security.php)
- [views/layouts/public.php](/Users/mattias/Development/frizon-web/views/layouts/public.php)
- [config/app.php](/Users/mattias/Development/frizon-web/config/app.php)
- [/.env.example](/Users/mattias/Development/frizon-web/.env.example)

### Motiv
- Minska risken för missbruk av kostsamma eller kvotbegränsade integrationer vid kapad session eller felande klientkod.
- Göra HTTPS-detektering säkrare bakom reverse proxy utan att tvinga fram en brytande ändring i befintlig drift.
- Få CSP-reglerna lite närmare faktisk implementation genom att nonce-märka strukturerad data som redan renderas inline.

## 2026-04-09 — Batch 5

### Ändringar
- Tog bort kvarvarande inline `onclick`-handlers för affiliate tracking i publika vyer.
- Ersatte inline-events med `data-affiliate-*`-attribut och central, nonce-skyddad event delegation i publik layout.
- Lade till riktade tester som verifierar att affiliate-markupen inte längre använder inline-JS och att JSON-LD-script fortsatt nonce-märks.
- Lade till riktade tester för proxy/CIDR-matchning i säkerhetshjälparen.

### Påverkade filer
- [views/partials/shop-card.php](/Users/mattias/Development/frizon-web/views/partials/shop-card.php)
- [views/public/shop-product.php](/Users/mattias/Development/frizon-web/views/public/shop-product.php)
- [views/public/place-detail.php](/Users/mattias/Development/frizon-web/views/public/place-detail.php)
- [views/layouts/public.php](/Users/mattias/Development/frizon-web/views/layouts/public.php)
- [tests/test_csp_markup.php](/Users/mattias/Development/frizon-web/tests/test_csp_markup.php)
- [tests/test_security_helpers.php](/Users/mattias/Development/frizon-web/tests/test_security_helpers.php)

### Motiv
- Eliminera kvarvarande CSP-avvikelser där inline event handlers annars kräver svagare script policy.
- Behålla affiliate-analytics utan att binda säkerhetskritik till HTML-attribut i templaten.

## 2026-04-09 — Batch 6

### Ändringar
- Införde explicit adminflagga via `users.is_admin` och uppdaterade auth-flödet så `/adm` kräver adminbehörighet.
- Gjorde auth-beteendet bakåtkompatibelt inför migration: befintliga sessioner och databaser utan `is_admin` fortsätter fungera tills migreringen körs.
- Lade till best-effort auditlogg via `security_audit_log` för kritiska auth- och adminhändelser:
  - login lyckad/misslyckad/throttlad
  - logout
  - lösenordsbyte
  - skapa/uppdatera/radera plats, besök, resa, lista
  - publicera/avpublicera/topplista
  - Instagram-publicering
- Lade till ny migration för adminflagga och auditlogg samt uppdaterade seed-data så seedade användare blir admins.
- Uppdaterade README med korrekt migreringsordning, prod-checklista och säkerhetsnoteringar.
- Lade till `scripts/check-production-config.php` för preflight av prodkonfiguration innan deploy.

### Påverkade filer
- [app/Services/Auth.php](/Users/mattias/Development/frizon-web/app/Services/Auth.php)
- [app/Services/SecurityAudit.php](/Users/mattias/Development/frizon-web/app/Services/SecurityAudit.php)
- [app/Controllers/AuthController.php](/Users/mattias/Development/frizon-web/app/Controllers/AuthController.php)
- [app/Controllers/PublishController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublishController.php)
- [app/Controllers/PlaceController.php](/Users/mattias/Development/frizon-web/app/Controllers/PlaceController.php)
- [app/Controllers/VisitController.php](/Users/mattias/Development/frizon-web/app/Controllers/VisitController.php)
- [app/Controllers/TripController.php](/Users/mattias/Development/frizon-web/app/Controllers/TripController.php)
- [app/Controllers/ListController.php](/Users/mattias/Development/frizon-web/app/Controllers/ListController.php)
- [database/migrations/013_security_controls.sql](/Users/mattias/Development/frizon-web/database/migrations/013_security_controls.sql)
- [database/seed.sql](/Users/mattias/Development/frizon-web/database/seed.sql)
- [README.md](/Users/mattias/Development/frizon-web/README.md)
- [scripts/check-production-config.php](/Users/mattias/Development/frizon-web/scripts/check-production-config.php)

### Motiv
- Minska blast radius om ytterligare användare eller komprometterade konton tillkommer senare.
- Få revisionsspår för kritiska åtgärder utan att bygga ett tungt backend-ramverk.
- Göra proddeploy säkrare och mer reproducerbar med tydligare migrering och preflight-kontroll.
