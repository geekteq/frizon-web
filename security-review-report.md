# Säkerhets- och kodgranskningsrapport för app.frizon.org

## 1. Executive summary
- Total risknivå: Medel till hög.
- Viktigaste verifierade problem: global skrivåtkomst för alla inloggade användare, stored admin-XSS via referrerstatistik, osäker secret-hantering där webprocessen skriver till `.env`, interna fel från tredjeparts-API:er läcker till klient/UI, och svag auth-/rate-limit-härdning.
- Bedömning: appen bör inte betraktas som säker för internetexponerad fleranvändardrift i nuvarande skick. För en strikt tvåanvändarmiljö minskar sannolikheten för missbruk, men konsekvensen av ett enda komprometterat konto är i praktiken total kontroll över allt innehåll och flera externa integrationer.
- Verifiering: jag gick igenom frontend, backend, routes, auth, upload, AI/Instagram/Amazon/ORS-flöden, headers, config och driftfiler. Alla befintliga PHP-tester i [tests](/Users/mattias/Development/frizon-web/tests) kördes och passerade, men de täcker nästan bara export- och hjälplogik, inte säkerhetskritiska flöden.

## 2. Projektöversikt
- Stack: custom PHP 8.1+ utan ramverk, MySQL/MariaDB, plain JS/CSS, Leaflet. Se [README.md](/Users/mattias/Development/frizon-web/README.md):14 och bootstrap i [public/index.php](/Users/mattias/Development/frizon-web/public/index.php):5.
- Arkitektur: en front controller, egen router, PHP-views och tunna controllers ovanpå PDO-modeller. Routing i [routes/web.php](/Users/mattias/Development/frizon-web/routes/web.php):3, dispatch i [app/Router.php](/Users/mattias/Development/frizon-web/app/Router.php):40.
- Authmodell: session-cookie, CSRF-token i session, login/logout/lösenordsbyte men inga roller eller behörighetsnivåer. Se [app/Services/Auth.php](/Users/mattias/Development/frizon-web/app/Services/Auth.php):14, [app/Services/CsrfService.php](/Users/mattias/Development/frizon-web/app/Services/CsrfService.php):7 och [app/bootstrap.php](/Users/mattias/Development/frizon-web/app/bootstrap.php):44.
- API-struktur: admin-API under `/adm/api/*`, publik shop under `/shop` och affiliate redirect under `/go/{slug}`. Känsliga integrationer: Anthropic, Meta/Instagram, Amazon-scraping och OpenRouteService.
- Känsliga ytor: admin CRUD för platser/besök/resor/listor/publicering, AI-generering, Instagram-publicering, bilduppladdning, kontaktformulär, affiliate tracking.
- Positivt: mycket SQL använder prepared statements och `PDO::ATTR_EMULATE_PREPARES=false` i [app/bootstrap.php](/Users/mattias/Development/frizon-web/app/bootstrap.php):27; uppladdade filer serveras via `realpath`-kontroll i [public/index.php](/Users/mattias/Development/frizon-web/public/index.php):13; Amazon-fetcher har tydlig domän/protokoll-allowlist i [app/Services/AmazonFetcher.php](/Users/mattias/Development/frizon-web/app/Services/AmazonFetcher.php):66.

## 3. Huvudfynd
### 1. Alla inloggade användare har i praktiken full adminrätt
- Allvarlighetsgrad: Hög
- Kategori: Verifierat problem
- OWASP: A01 Broken Access Control, A04 Insecure Design
- Plats: [routes/web.php](/Users/mattias/Development/frizon-web/routes/web.php):23, [app/Controllers/PlaceController.php](/Users/mattias/Development/frizon-web/app/Controllers/PlaceController.php):20, [app/Controllers/VisitController.php](/Users/mattias/Development/frizon-web/app/Controllers/VisitController.php):26, [app/Controllers/TripController.php](/Users/mattias/Development/frizon-web/app/Controllers/TripController.php):27, [app/Controllers/ListController.php](/Users/mattias/Development/frizon-web/app/Controllers/ListController.php):22, [app/Controllers/PublishController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublishController.php):21
- Beskrivning: hela adminytan skyddas bara av `Auth::requireLogin()`. Det finns inga roller, inga policy checks och inga ownership-kontroller innan läs/skriv/delete/publicering/export.
- Risk: ett enda komprometterat konto ger total åtkomst till allt innehåll, alla publiceringsflöden och externa integrationer.
- Attackscenario / felutfall: användare B kan ändra, publicera, avpublicera eller radera användare A:s platser, besök, resor, listor och bilder genom att gissa slug/id eller använda UI:t direkt.
- Rekommenderad åtgärd: inför server-side authorization med minst `role`/`is_admin` och, om data ska vara per användare, konsekventa ownership-kontroller på modell- eller policy-nivå.
- Kodexempel eller patch-idé: skapa `AuthorizationService::requireOwnerOrAdmin($resourceOwnerId)` och kalla den före varje read/write/delete/export/publicera-endpoint.

### 2. Stored admin-XSS via osanitiserad referrer-länk i statistikvyn
- Allvarlighetsgrad: Hög
- Kategori: Verifierat problem
- OWASP: A03 Injection
- Plats: [app/Controllers/AmazonController.php](/Users/mattias/Development/frizon-web/app/Controllers/AmazonController.php):585, [app/Controllers/DashboardController.php](/Users/mattias/Development/frizon-web/app/Controllers/DashboardController.php):55, [views/dashboard/stats.php](/Users/mattias/Development/frizon-web/views/dashboard/stats.php):67
- Beskrivning: appen lagrar rå `HTTP_REFERER` i `product_clicks` och återrenderar sedan värdet direkt i `<a href="...">`. `htmlspecialchars()` stoppar inte `javascript:`- eller andra farliga schemes i `href`.
- Risk: attacker kan plantera en klickbar payload i adminens statistikvy.
- Attackscenario / felutfall: angriparen anropar `/go/{slug}` med `Referer: javascript:alert(document.cookie)`; när admin klickar länken i statistikvyn körs JS i adminkontext.
- Rekommenderad åtgärd: visa referrer som text, inte som klickbar länk, eller allowlista endast `https?` och normalisera URL med strikt parser.
- Kodexempel eller patch-idé: `if (!preg_match('#^https?://#i', $referrer)) { $href = null; }`.

### 3. Webprocessen skriver om `.env` i drift
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A05 Security Misconfiguration
- Plats: [app/Services/InstagramService.php](/Users/mattias/Development/frizon-web/app/Services/InstagramService.php):272
- Beskrivning: Instagram-token refresh uppdaterar `.env` från applikationskoden med `file_put_contents`.
- Risk: hemligheter muteras på disk från webprocessen, kräver skrivbar app-root, skapar drift-drift och race conditions och försvårar secrets rotation/audit.
- Attackscenario / felutfall: felaktiga filrättigheter eller parallella requests kan korrupta `.env`; komprometterad appprocess får dessutom direkt skrivrätt till secrets-filen.
- Rekommenderad åtgärd: flytta secrets till riktig secret store eller separat credential storage utanför repo/app-root. Refreshad token ska lagras i DB/secret manager, inte i `.env`.
- Kodexempel eller patch-idé: ersätt `updateEnv()` med ett `CredentialRepository` som skriver till krypterad eller åtkomststyrd server-side storage.

### 4. Interna providerfel läcker till klient och admin-UI
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A05 Security Misconfiguration, A09 Security Logging and Monitoring Failures
- Plats: [app/Controllers/AiController.php](/Users/mattias/Development/frizon-web/app/Controllers/AiController.php):68, [app/Controllers/VisitController.php](/Users/mattias/Development/frizon-web/app/Controllers/VisitController.php):307, [app/Controllers/VisitController.php](/Users/mattias/Development/frizon-web/app/Controllers/VisitController.php):399, [app/Controllers/PublishController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublishController.php):78, [app/Services/AiService.php](/Users/mattias/Development/frizon-web/app/Services/AiService.php):144, [app/Services/InstagramService.php](/Users/mattias/Development/frizon-web/app/Services/InstagramService.php):347
- Beskrivning: raw `RuntimeException`-meddelanden från Claude/Instagram visas i JSON-svar eller flash-meddelanden.
- Risk: interna felkedjor, provider-respons och konfigurationsdetaljer exponeras onödigt och förenklar felsökning för angripare.
- Attackscenario / felutfall: klienten får ut text som `ANTHROPIC_API_KEY saknas`, `Claude API-fel: ...` eller full Meta Graph error text.
- Rekommenderad åtgärd: logga detaljer server-side, returnera generiska fel till klienten och gärna en korrelations-id.
- Kodexempel eller patch-idé: `catch (...) { error_log(...); echo json_encode(['success'=>false,'error'=>'Tjänsten kunde inte slutföra begäran']); }`.

### 5. Auth-härdningen är tunn för en internetexponerad adminyta
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A07 Identification and Authentication Failures
- Plats: [app/Controllers/AuthController.php](/Users/mattias/Development/frizon-web/app/Controllers/AuthController.php):27, [app/Services/LoginThrottle.php](/Users/mattias/Development/frizon-web/app/Services/LoginThrottle.php):22, [app/Controllers/AuthController.php](/Users/mattias/Development/frizon-web/app/Controllers/AuthController.php):86, [app/bootstrap.php](/Users/mattias/Development/frizon-web/app/bootstrap.php):44
- Beskrivning: endast enkel användarnamn+IP-throttle, inget konto- eller globalt IP-skydd, ingen MFA, minsta lösenordslängd 8 tecken, sessionslivslängd 7 dagar.
- Risk: credential stuffing, distribuerad brute force och långlivade sessionskapningar blir mer realistiska än nödvändigt.
- Attackscenario / felutfall: attacker sprider försök över flera IP:n eller användarnamn och kommer runt throttle-logiken; komprometterad session lever länge.
- Rekommenderad åtgärd: lägg till konto-baserad lockout/backoff, global IP rate limiting, starkare lösenordspolicy och helst MFA för `/adm`.
- Kodexempel eller patch-idé: throttla både per konto och per IP-prefix samt korta sessionstiden markant för admin.

### 6. Kontaktformens antispam-token kan återanvändas obegränsat länge
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A04 Insecure Design
- Plats: [app/Controllers/PublicController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublicController.php):441, [public/sw.js](/Users/mattias/Development/frizon-web/public/sw.js):97
- Beskrivning: `form_token` är bara `HMAC(loaded_at, APP_KEY)` och valideras utan maxålder. Koden stoppar bara för snabba submits, inte gamla eller replayade formulär.
- Risk: en gammal eller cachad formulärsida kan återanvändas för spam så länge `APP_KEY` är oförändrad.
- Attackscenario / felutfall: angriparen återspelar ett gammalt formulär med giltigt `loaded_at`/`form_token`; service worker kan dessutom återservera äldre HTML.
- Rekommenderad åtgärd: bind token till session och ge den TTL, eller använd en riktig one-time nonce/server-side challenge.
- Kodexempel eller patch-idé: lagra `contact_form_nonce` i session med `created_at`, acceptera max 15 minuter och invalidisera efter användning.

### 7. Säkerhetsbeslut styrs av okontrollerad `X-Forwarded-Proto`
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A05 Security Misconfiguration
- Plats: [app/Helpers/security.php](/Users/mattias/Development/frizon-web/app/Helpers/security.php):5, [app/bootstrap.php](/Users/mattias/Development/frizon-web/app/bootstrap.php):48
- Beskrivning: `app_is_https_request()` litar direkt på `HTTP_X_FORWARDED_PROTO`, och resultatet används för `Secure`-flagga och HSTS.
- Risk: om appen nås utan strikt betrodd reverse proxy kan klient- eller mellanliggande header påverka säkerhetsbeteendet.
- Attackscenario / felutfall: fel proxykonfiguration kan göra att sessionscookies sätts fel eller att HSTS inte aktiveras korrekt.
- Rekommenderad åtgärd: lita bara på forwarded headers från explicit trusted proxy, annars använd endast serverlokal TLS-signal.
- Kodexempel eller patch-idé: lägg till `TRUSTED_PROXIES` och ignorera `X-Forwarded-Proto` om `REMOTE_ADDR` inte är en betrodd proxy.

### 8. Produktionskritiska funktioner faller tyst tillbaka till falska data/providers
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A04 Insecure Design, A08 Software and Data Integrity Failures
- Plats: [app/Services/AiService.php](/Users/mattias/Development/frizon-web/app/Services/AiService.php):453, [app/Services/Routing/OpenRouteServiceProvider.php](/Users/mattias/Development/frizon-web/app/Services/Routing/OpenRouteServiceProvider.php):44, [README.md](/Users/mattias/Development/frizon-web/README.md):5
- Beskrivning: okänd eller saknad `AI_PROVIDER` ger `FakeAiProvider`, och route-API-fel ger `FakeRouteProvider` utan hårt fel till användaren.
- Risk: systemet kan producera plausibla men felaktiga texter och ruttdata i drift utan tydlig signal om att externa beroenden faktiskt fallerat.
- Attackscenario / felutfall: felkonfigurerad prodmiljö publicerar test-/placeholderdata eller exporterar rutter som aldrig beräknats på riktigt.
- Rekommenderad åtgärd: gör fake providers till explicit dev/test-only med hårt fail i produktion.
- Kodexempel eller patch-idé: `if (APP_ENV==='production' && provider==='fake') throw new RuntimeException(...)`.

### 9. Bilduppladdning saknar skydd mot högupplösta lågbytesbilder
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A04 Insecure Design
- Plats: [app/Services/ImageService.php](/Users/mattias/Development/frizon-web/app/Services/ImageService.php):24
- Beskrivning: uppladdning begränsar filstorlek men inte pixeldimensioner eller total pixel count innan GD laddar och resamplar bilden.
- Risk: en relativt liten men extremt högupplöst bild kan ge hög minnesförbrukning eller OOM i `imagecreatefrom*`/`imagecreatetruecolor`.
- Attackscenario / felutfall: en autentiserad användare laddar upp en manipulerad PNG/WebP som kraschar PHP-processen eller binder worker-minne.
- Rekommenderad åtgärd: avvisa bilder över max bredd/höjd eller total pixel count innan någon bilddekodning som allokerar stor memorybuffer.
- Kodexempel eller patch-idé: kontrollera `getimagesize()`-resultatet och neka t.ex. `width * height > 40_000_000`.

### 10. Dependency- och deploymentspårbarheten är för svag
- Allvarlighetsgrad: Medel
- Kategori: Verifierat problem
- OWASP: A06 Vulnerable and Outdated Components, A08 Software and Data Integrity Failures
- Plats: repo-root utan `composer.json`, `composer.lock`, `package.json`, CI eller containerfiler; [public/leaflet/leaflet.js](/Users/mattias/Development/frizon-web/public/leaflet/leaflet.js):2; [README.md](/Users/mattias/Development/frizon-web/README.md):29; [config/app.php](/Users/mattias/Development/frizon-web/config/app.php):6; [.env.example](/Users/mattias/Development/frizon-web/.env.example):3
- Beskrivning: det finns ingen låst dependency inventory, ingen automatisk SCA/CI, och README instruerar bara första migrationen trots att flera migrationsfiler finns. Exempelkonfigen har dessutom `APP_DEBUG=true`.
- Risk: sårbarhetsinventering och reproducerbar deploy blir svåra; felaktig setup kan ge schema-drift eller dev-liknande produktionsläge.
- Attackscenario / felutfall: ny server sätts upp med ofullständig schema eller med debug/fake-provider-defaults.
- Rekommenderad åtgärd: inför manifest/lockfiles eller åtminstone en explicit dependency inventory, CI med test/SCA, och ett riktigt migreringssteg som kör samtliga migrationer.
- Kodexempel eller patch-idé: skapa enkel deploy-check som failar om `APP_DEBUG=true`, `AI_PROVIDER=fake` eller ej fullständig migreringsnivå i produktion.

## 4. OWASP Top 10 checklista
- A01 Broken Access Control  
Status: Problem hittat.  
Vad jag kontrollerat: admin-routes, CRUD, export, publicering, AI/Instagram-endpoints.  
Vad jag hittat: inga roller/ägarkontroller; en inloggad användare får global write/read/delete/publicera-exportera-åtkomst.  
Vad som saknas: manuell tvåanvändartest för att bekräfta exakt affärskrav.

- A02 Cryptographic Failures  
Status: Misstanke.  
Vad jag kontrollerat: lösenordshashning, sessioncookie, APP_KEY-användning, SMTP TLS.  
Vad jag hittat: `password_hash()`/`password_verify()` är bra; sessioncookies har `HttpOnly` och `SameSite=Lax`; men secrets lagras platt i `.env` och modifieras i drift.  
Vad som saknas: faktisk driftkonfiguration för TLS, filrättigheter och secret storage.

- A03 Injection  
Status: Problem hittat.  
Vad jag kontrollerat: SQL, XSS, DOM-skrivningar, länkar, JSON-rendering.  
Vad jag hittat: ingen tydlig SQLi; däremot stored admin-XSS/unsafe URL-scheme via referrerlänken i statistik.  
Vad som saknas: manuell browserverifiering av exploit och CSP-beteende.

- A04 Insecure Design  
Status: Problem hittat.  
Vad jag kontrollerat: trust boundaries, fake fallbacks, antispamdesign, upload limits.  
Vad jag hittat: global admintrust, replaybar kontakt-token, fake providers i drift och ingen pixelgräns på bilder.  
Vad som saknas: exakt avsedd trustmodell för “två användare”.

- A05 Security Misconfiguration  
Status: Problem hittat.  
Vad jag kontrollerat: headers, proxy trust, debug-defaults, runtime-config, error responses.  
Vad jag hittat: `X-Forwarded-Proto` litas på för säkerhetsbeslut, `.env` skrivs i drift, `APP_DEBUG=true` i exempel, och interna providerfel exponeras.  
Vad som saknas: faktisk proxy- och webserverkonfiguration.

- A06 Vulnerable and Outdated Components  
Status: Misstanke.  
Vad jag kontrollerat: dependency inventory, versionpinning, vendorerade bibliotek.  
Vad jag hittat: repo:t saknar manifests/lockfiles/SCA; endast vendorerad Leaflet 1.9.4 är tydligt synlig.  
Vad som saknas: full dependency-lista och automatiserad CVE-scan.

- A07 Identification and Authentication Failures  
Status: Problem hittat.  
Vad jag kontrollerat: login-throttle, sessioninställningar, lösenordsbyte.  
Vad jag hittat: enkel username+IP-throttle, svag lösenordspolicy, lång session, ingen MFA.  
Vad som saknas: brute-force-test mot verklig driftmiljö.

- A08 Software and Data Integrity Failures  
Status: Problem hittat.  
Vad jag kontrollerat: build/deploy-kedja, providerfokuserade fallbacks, runtime-ändringar i config.  
Vad jag hittat: fake providers i produktion är möjliga, `.env` muteras i drift, ingen CI/signeringskedja.  
Vad som saknas: faktisk releaseprocess.

- A09 Security Logging and Monitoring Failures  
Status: Problem hittat.  
Vad jag kontrollerat: loginloggar, auditlogg för adminåtgärder, alerter.  
Vad jag hittat: nästan ingen säkerhets- eller auditloggning utöver sporadisk `error_log`; inget spår av central övervakning.  
Vad som saknas: driftmiljöns logg- och alertingstack.

- A10 Server-Side Request Forgery  
Status: Ingen tydlig risk.  
Vad jag kontrollerat: cURL-anrop, user-controlled URLs, bildhämtning.  
Vad jag hittat: AmazonFetcher allowlistar Amazon/Amazon-image-domäner och HTTPS; övriga integrationer går mot fasta endpoints.  
Vad som saknas: nätverksnivåpolicy i drift och manuell verifiering av redirect-edge-cases.

## 5. Övriga kodproblem
- Sannolikt problem: CSP är inte i linje med templaten. `script-src` kräver nonce i [app/Helpers/security.php](/Users/mattias/Development/frizon-web/app/Helpers/security.php):67, men JSON-LD-script saknar nonce i [views/layouts/public.php](/Users/mattias/Development/frizon-web/views/layouts/public.php):41 och affiliate-click analytics använder inline `onclick` i [views/public/place-detail.php](/Users/mattias/Development/frizon-web/views/public/place-detail.php):120 och [views/public/shop-product.php](/Users/mattias/Development/frizon-web/views/public/shop-product.php):48. Det är minst en funktionalitetsbugg och ger falsk trygghet kring CSP.
- Verifierat problem: service worker cache:ar publika HTML-sidor i [public/sw.js](/Users/mattias/Development/frizon-web/public/sw.js):97. Det ökar risken för stale content, gamla formulär och svårdebuggade sessions-/cachefel.
- Verifierat problem: view-lagret använder `extract($data)` i [app/Helpers/view.php](/Users/mattias/Development/frizon-web/app/Helpers/view.php):5. Det är inte en direkt sårbarhet här, men gör templaten skörare och mer kollisionskänsliga.
- Verifierat problem: `PublishController::approve()` gör platsen publik innan SEO-generering lyckats i [app/Controllers/PublishController.php](/Users/mattias/Development/frizon-web/app/Controllers/PublishController.php):54. Det är en medveten partiell commit, men skapar inkonsekventa tillstånd.
- Verifierat problem: testsviten är tunn och fokuserar på hjälplogik. Den täcker inte auth, CSRF, access control, upload, felhantering eller externa integrationer. Se [tests](/Users/mattias/Development/frizon-web/tests).

## 6. Konfigurations- och driftproblem
- `.env.example` har osäkra eller förvirrande defaults: `APP_DEBUG=true`, `AI_PROVIDER=fake` och exempelvärden för SMTP i [/.env.example](/Users/mattias/Development/frizon-web/.env.example):3.
- README:s setup kör bara första migrationen i [README.md](/Users/mattias/Development/frizon-web/README.md):29, trots att flera migrationsfiler finns. Det är en konkret deploy-risk.
- Repo:t saknar CI/CD, Docker, GitHub Actions och automatiserad policy enforcement. Det gör att test, SCA och releasekontroller måste ske manuellt.
- Headers är i huvudsak bra: CSP, HSTS, `X-Frame-Options`, `nosniff` och restriktiv default CORS. Det som faller är främst CSP-konsistensen och proxy-trusten.
- Rate limiting finns bara för login och kontaktform. Det finns inget motsvarande skydd för tunga admin-API:er som AI-generering, Instagram-publicering eller bilduppladdning.
- Jag såg inget som visar backup/recovery-strategi, revisionsloggning eller central monitorering.

## 7. Beroenden och tredjepartsrisker
- Verifierat problem: ingen komplett dependency inventory. Utan `composer.lock`/`package-lock` går det inte att göra en trovärdig repo-baserad CVE-genomgång.
- Verifierat problem: Leaflet är vendorerat manuellt som 1.9.4 i [public/leaflet/leaflet.js](/Users/mattias/Development/frizon-web/public/leaflet/leaflet.js):2. Det innebär manuell patchhantering.
- Verifierat problem: README säger PHP 8.1+ i [README.md](/Users/mattias/Development/frizon-web/README.md):16. Om produktion faktiskt kör 8.1 är det nu en för gammal branch; enligt PHP:s officiella supported versions-sida är 8.1 redan end-of-life per 2026-04-09: https://www.php.net/supported-versions.php
- Sannolikt problem: externa integrationer mot Anthropic, Meta Graph, Amazon och OpenRouteService saknar tydlig circuit breaking, quota-monitorering och dedikerad secret manager.

## 8. Prioriterad åtgärdsplan
- Akut att fixa nu  
Skapa riktig server-side authorization för alla admin-endpoints.  
Ta bort klickbara råa referrerlänkar eller allowlista schemes strikt.  
Sluta skriva secrets till `.env` från webbappen.  
Sluta returnera råa tredjepartsfel till klient/UI.

- Bör fixas denna vecka  
Hårdgör login med konto- och IP-baserad rate limiting och starkare lösenordspolicy.  
Lägg in auditlogg för login, publicering, delete och integrationer.  
Validera bilduppladdning på pixel count/dimensioner, inte bara byte.  
Gör fake providers otillåtna i produktion.

- Bör fixas snart  
Rätta CSP kontra inline JSON-LD/onclick.  
Inför dependency manifests/lockfiles och enkel CI med test + SCA.  
Rätta README/deploy så alla migrationer körs.

- Kan vänta  
Städa template-lagret (`extract`), förfina service worker-cachelogik och förbättra testtäckningen.

## 9. Snabba vinster
- Rendera referrer som text i statistikvyn och gör den inte klickbar.
- Inför `is_admin` i `users` och en central `requireAdmin()`.
- Byt alla råa `$e->getMessage()` mot generiska fel till klienten.
- Lägg in max pixel count för uppladdade bilder.
- Faila hårt i produktion om `AI_PROVIDER=fake` eller om Instagram-token inte kan läsas från säker lagring.
- Lägg till `public/ig/*` i [/.gitignore](/Users/mattias/Development/frizon-web/.gitignore):1 eller flytta genererade JPEG:er till `storage/`.
- Sätt `APP_DEBUG=false` i exempel- och produktionsmallar.

## 10. Misstankar som kräver manuell testning
- Testa IDOR med två separata konton mot `/adm/platser/{slug}`, `/adm/besok/{id}`, `/adm/resor/{slug}`, `/adm/listor/{id}` och `/adm/api/*`.
- Verifiera stored XSS genom att logga en manipulerad `Referer` mot `/go/{slug}` och sedan klicka länken i statistikvyn.
- Verifiera CSP i riktig browser: kontrollera om JSON-LD blockeras och om affiliate-click analytics faktiskt körs eller stoppas av CSP.
- Testa appen bakom verklig reverse proxy för att bekräfta att `X-Forwarded-Proto` inte kan spoofas från klient.
- Fuzza bilduppladdning med högupplösta men små filer och observera minnesförbrukning/worker-stabilitet.
- Bekräfta i drift om det finns extern loggning/alerting som inte syns i repo:t.

