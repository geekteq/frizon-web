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
3. Run the migration:
   ```bash
   mysql -u root frizon < database/migrations/001_initial_schema.sql
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
