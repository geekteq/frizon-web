# Frizon.org

Privat resedagbok och reseplanerare for Mattias & Ulrica — Frizon of Sweden.

Built for two users tracking campervan travels with Frizze (Adria Twin SPT 600 Platinum 2017).

## Features

- **Travel log** — Save places from GPS, add notes, rate from a campervan perspective
- **Trip planning** — Create trips with ordered stops, route calculation, checklists
- **Public publishing** — Selectively publish places with AI-assisted descriptions
- **Export** — GPX for Garmin, CSV, JSON, Google Maps links

## Tech Stack

- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.6+
- LiteSpeed (or Apache with mod_rewrite)
- Leaflet.js for maps
- Plain CSS + JS, no framework

## Setup

1. Copy `.env.example` to `.env` and configure database credentials
2. Create the database:
   ```sql
   CREATE DATABASE frizon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Run all migrations in order:
   ```bash
   for f in database/migrations/*.sql; do
     mysql -u root frizon < "$f"
   done
   ```
4. Optionally seed test data:
   ```bash
   mysql -u root frizon < database/seed.sql
   ```
5. Point your web server document root to `public/`
6. Create upload directories (already exist via git):
   ```bash
   mkdir -p storage/uploads/{originals,thumbnails,cards,detail}
   ```
7. Ensure PHP GD or Imagick extension is enabled (for image resizing)

## Production Checklist

1. Set production-safe env values in `.env`:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `AI_PROVIDER=claude`
   - `ANTHROPIC_API_KEY=...`
   - `APP_KEY=...`
2. If the app sits behind a trusted reverse proxy and you want strict forwarded-header trust:
   - set `ENFORCE_TRUSTED_PROXIES=true`
   - set `TRUSTED_PROXIES=` to proxy IP or CIDR list
3. Ensure these directories exist and are writable by PHP:
   ```bash
   mkdir -p storage/uploads/{originals,thumbnails,cards,detail} storage/runtime-secrets
   ```
4. Run the config preflight:
   ```bash
   php scripts/check-production-config.php
   ```
5. Run the test suite:
   ```bash
   for f in tests/*.php; do
     php "$f" || exit 1
   done
   ```

## Security Notes

- Migration `013_security_controls.sql` adds `users.is_admin` and `security_audit_log`.
- Existing users are promoted to admin by the migration so current access keeps working.
- Seeded users are also marked as admins.
- Audit logging is best-effort: if the log table is unavailable the app should keep functioning while emitting server log warnings.

## Project Structure

```
public/          Web root (index.php, CSS, JS, images)
app/             Application code (controllers, models, services, helpers)
config/          Configuration files
views/           PHP templates
database/        Migrations and seed data
storage/uploads/ Uploaded images (not in git)
routes/          Route definitions
tests/           Test scripts
docs/            Specifications and design documents
```

## UI Language

Swedish. All labels, navigation, and user-facing text is in Swedish.

## Documentation

- `docs/SPEC.md` — Full product specification
- `docs/UI-DESIGN.md` — Complete UI/UX design system
- `docs/PHASES.md` — Implementation phases
