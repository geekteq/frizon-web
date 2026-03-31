# Security Code Review

Date: 2026-03-31

Scope: full repository review with emphasis on OWASP Top 10 risks, authentication/session handling, file upload/serving, public/private data boundaries, and client-side injection sinks.

No application code was changed as part of this review.

Validation performed:
- `php -l` across all PHP files: passed
- `php tests/*.php`: passed

## Findings

### 1. Critical: arbitrary file read via path traversal in `/uploads/...`

- OWASP: A01 Broken Access Control, A05 Security Misconfiguration
- Evidence: `public/index.php:11-17`
- The upload-serving route concatenates the requested filename directly into a filesystem path:
  - `storage/uploads/<variant>/` + user-controlled remainder from `(.+)`
- Because `(.+)` allows `../`, a request such as `/uploads/cards/../../../.env` resolves outside the upload directory and can read arbitrary files the PHP process can access.
- Impact:
  - disclosure of `.env`, database credentials, API keys, application source, or other secrets
  - complete compromise of confidentiality, and likely follow-on admin compromise
- Recommended fix:
  - resolve the candidate path with `realpath()`
  - verify the resolved path starts with the intended base directory
  - reject any path containing traversal segments before filesystem access
  - stop serving `originals` directly unless there is a strong requirement
  - add `X-Content-Type-Options: nosniff`

### 2. High: stored XSS through JS-generated HTML from database-backed values

- OWASP: A03 Injection
- Evidence:
  - `public/js/tags.js:25-27`
  - `public/js/trips.js:40-42`
  - `views/public/homepage.php:96-100`
  - `views/public/place-detail.php:90`
- Several places build HTML strings with unescaped values that originate from stored content:
  - tag suggestions are written with `dropdown.innerHTML`
  - place names are interpolated into Leaflet popups with HTML strings
- `place.name` and `visits.suitable_for` are user-controlled inputs elsewhere in the app, so an attacker who can save crafted content can execute JavaScript when those pages load.
- Impact:
  - session theft
  - CSRF token theft
  - arbitrary admin actions in the victim’s browser
  - public-page defacement if a malicious place name is published
- Recommended fix:
  - stop using `innerHTML` and string-built popup HTML for untrusted data
  - build DOM nodes with `textContent`
  - sanitize server-side before persistence where appropriate
  - add a restrictive CSP as defense in depth

### 3. High: state-changing endpoints missing CSRF enforcement

- OWASP: A01 Broken Access Control, A05 Security Misconfiguration
- Evidence:
  - `app/Controllers/ListController.php:162-170`
  - `app/Controllers/ListController.php:191-204`
  - `app/Controllers/TripController.php:190-207`
  - `app/Controllers/VisitController.php:182-200`
  - `routes/web.php:75-77`
  - `routes/web.php:93`
- The following mutating endpoints require authentication but do not verify CSRF tokens:
  - checklist item toggle
  - list reorder
  - trip stop reorder
  - image upload API
- The frontend sends `X-CSRF-Token` on some of these requests, but the controllers do not enforce it.
- Impact:
  - cross-site requests can change application state for logged-in users
  - forced uploads can consume storage and trigger image processing
  - cross-site tampering with trip/list ordering is possible
- Recommended fix:
  - require CSRF validation for every non-GET endpoint
  - reject requests without a valid token even when called through JSON/fetch
  - consider centralizing this check in routing/middleware instead of per-controller

### 4. Medium: public pages leak unpublished/private visit aggregates

- OWASP: A01 Broken Access Control
- Evidence:
  - `app/Controllers/PublicController.php:21-29`
  - `app/Controllers/PublicController.php:92-99`
  - `app/Controllers/PublicController.php:119-127`
- Public pages correctly filter displayed visit bodies and images with `ready_for_publish = 1`, but the aggregate queries do not:
  - homepage ratings and visit counts include all visits for a public place
  - place detail average rating includes all visits for that place
  - toplist ratings and visit counts include all visits
- This leaks private, unpublished diary data into public counters and averages.
- Impact:
  - public visitors can infer unpublished visits and unpublished ratings
  - breaks the documented “human approval before publishing” boundary
- Recommended fix:
  - apply `ready_for_publish = 1` consistently to all public aggregates
  - review whether `public_allowed` alone is sufficient or whether publication should be visit-specific everywhere

### 5. Medium: session cookie hardening is not enforced by the application

- OWASP: A07 Identification and Authentication Failures
- Evidence:
  - `app/bootstrap.php:33-34`
  - `app/Services/Auth.php:45-55`
- Sessions are started with `session_start()` without explicit cookie settings such as:
  - `Secure`
  - `HttpOnly`
  - `SameSite=Lax` or `Strict`
  - `session.use_strict_mode=1`
- The logout code only mirrors whatever cookie parameters are already in effect; it does not harden them.
- Impact:
  - production security depends entirely on external PHP/webserver defaults
  - weaker deployments are exposed to session theft or weaker CSRF/session-fixation posture
- Recommended fix:
  - call `session_set_cookie_params()` before `session_start()`
  - explicitly set secure defaults in app bootstrap
  - enable strict mode and regenerate IDs on privilege changes

### 6. Medium: login flow has no brute-force or abuse controls

- OWASP: A07 Identification and Authentication Failures
- Evidence:
  - `app/Controllers/AuthController.php:27-45`
  - `app/Services/Auth.php:14-27`
- The login endpoint performs direct username/password verification with no visible:
  - rate limiting
  - lockout/backoff
  - audit logging
  - IP-based protection
- The admin login is publicly reachable at `/adm/login`, so it is a viable online attack surface.
- Impact:
  - credential stuffing and password guessing are not meaningfully slowed down
  - risk increases if usernames are predictable or reused elsewhere
- Recommended fix:
  - add per-IP and per-username throttling
  - log failed login attempts
  - consider a reverse-proxy rate limit or fail2ban-style control in addition to app logic

### 7. Medium: baseline security headers are missing

- OWASP: A05 Security Misconfiguration
- Evidence:
  - `public/.htaccess:1-4`
  - repository search found no application-level `Content-Security-Policy`, `Strict-Transport-Security`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, or `Permissions-Policy`
- The current web-server config only rewrites to `index.php`; it does not establish a baseline browser hardening policy.
- Impact:
  - XSS becomes easier to exploit and harder to contain
  - clickjacking remains possible
  - MIME sniffing is not disabled
  - HTTPS policy is not enforced from the app/server config in this repo
- Recommended fix:
  - add CSP, `X-Frame-Options` or `frame-ancestors`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, and HSTS at the web server
  - remove or minimize inline scripts to make a strict CSP practical

## Priority Order

1. Fix the `/uploads/...` path traversal immediately.
2. Eliminate stored XSS sinks in `tags.js`, trip map popups, and public map popups.
3. Enforce CSRF validation on all mutating endpoints.
4. Correct public aggregate queries so unpublished/private visit data never influences public output.
5. Harden session cookies and add login abuse controls.
6. Add baseline security headers and a CSP.

## Notes

- I did not review third-party infrastructure configuration outside this repository.
- I did not make any code changes yet, per request.
