# Phase 2 — Trips, Routing & GPX Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add trip management with ordered stops, road routing via OpenRouteService, route summaries, and GPX export for Garmin.

**Architecture:** Extends the existing Phase 1 MVC app. Trip model owns ordered TripStops that reference Places. RouteProvider abstraction decouples routing from any specific API. GpxTripExporter produces Garmin-compatible GPX files. All views follow the existing pattern (PHP templates, Swedish UI, mobile-first CSS).

**Tech Stack:** PHP 8.x, MySQL/MariaDB, PDO, Leaflet.js, OpenRouteService API, plain CSS/JS.

**Reference docs:**
- `docs/SPEC.md` — sections: Domain concepts, Routing, Exports, Database (trips, trip_stops, trip_route_segments)
- `docs/UI-DESIGN.md` — sections 3.8-3.11 (trip views), 6.4 (route polyline)
- `CLAUDE.md` — ETA 95 formula: `round(distance_km / 95 * 60)`

**Existing patterns to follow:**
- Models: `app/Models/Place.php` — constructor takes PDO, methods return arrays
- Controllers: `app/Controllers/PlaceController.php` — constructor takes PDO + config, methods take $params array
- Views: `views/places/` — PHP templates using `view()` helper, Swedish labels
- Routes: `routes/web.php` — `registerRoutes(Router $router)` function
- CSS: `public/css/` — component CSS with `@import` in main.css, class names matching HTML

---

## File Structure

```
database/
  migrations/
    002_trips_schema.sql           # trips, trip_stops, trip_route_segments tables

app/
  Models/
    Trip.php                       # Trip CRUD, status filtering
    TripStop.php                   # Stop CRUD, ordering, reordering
    TripRouteSegment.php           # Route segment storage

  Controllers/
    TripController.php             # Trip CRUD + stop management + export

  Services/
    Routing/
      RouteProviderInterface.php   # Interface: getRoute(from, to) → segment data
      OpenRouteServiceProvider.php # ORS implementation
      FakeRouteProvider.php        # Returns fake data for local testing
    Export/
      GpxTripExporter.php          # GPX file generation

views/
  trips/
    index.php                      # Trip list grouped by status
    create.php                     # Create trip form
    show.php                       # Trip detail with stops, route, export
    edit.php                       # Edit trip form

  partials/
    trip-card.php                  # Trip card for index
    stop-card.php                  # Stop card for trip detail

routes/
  web.php                          # Add trip routes (modify)

public/
  css/
    pages/trips.css                # Trip-specific styles
    main.css                       # Add trips.css import (modify)
  js/
    trips.js                       # Stop reordering, route map

tests/
  test_eta95.php                   # ETA 95 calculation test
  test_gpx_export.php              # GPX output structure test
  test_stop_ordering.php           # Stop reorder logic test
```

---

## Task 1: Database migration for trips

**Files:**
- Create: `database/migrations/002_trips_schema.sql`

- [ ] **Step 1: Create the migration**

```sql
-- Frizon.org Phase 2 Schema
-- Tables: trips, trip_stops, trip_route_segments

CREATE TABLE IF NOT EXISTS trips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    intro_text TEXT NULL,
    public_summary TEXT NULL,
    cover_image_path VARCHAR(500) NULL,
    status ENUM('planned','ongoing','finished') NOT NULL DEFAULT 'planned',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_trips_status (status),
    INDEX idx_trips_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trip_stops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    place_id INT UNSIGNED NOT NULL,
    stop_order INT UNSIGNED NOT NULL DEFAULT 0,
    stop_type ENUM(
        'breakfast','lunch','dinner','fika','sight',
        'shopping','stellplatz','wild_camping','camping'
    ) NULL,
    planned_at DATETIME NULL,
    arrival_at DATETIME NULL,
    departure_at DATETIME NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES places(id),
    INDEX idx_stops_trip (trip_id),
    INDEX idx_stops_order (trip_id, stop_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trip_route_segments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    from_stop_id INT UNSIGNED NOT NULL,
    to_stop_id INT UNSIGNED NOT NULL,
    distance_km DECIMAL(8,2) NULL,
    provider_eta_minutes INT UNSIGNED NULL,
    eta_95_minutes INT UNSIGNED NULL,
    geometry MEDIUMTEXT NULL COMMENT 'Encoded polyline or GeoJSON',
    provider_name VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (from_stop_id) REFERENCES trip_stops(id) ON DELETE CASCADE,
    FOREIGN KEY (to_stop_id) REFERENCES trip_stops(id) ON DELETE CASCADE,
    INDEX idx_segments_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run the migration**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon < database/migrations/002_trips_schema.sql
```

- [ ] **Step 3: Verify tables created**

```bash
/opt/homebrew/opt/mariadb/bin/mysql -u root frizon -e "SHOW TABLES LIKE 'trip%';"
```
Expected: trips, trip_stops, trip_route_segments

- [ ] **Step 4: Commit**

```bash
git add database/migrations/002_trips_schema.sql
git commit -m "feat: Phase 2 schema — trips, trip_stops, trip_route_segments"
```

---

## Task 2: Trip and TripStop models

**Files:**
- Create: `app/Models/Trip.php`
- Create: `app/Models/TripStop.php`
- Create: `app/Models/TripRouteSegment.php`

- [ ] **Step 1: Create `app/Models/Trip.php`**

```php
<?php

declare(strict_types=1);

class Trip
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('
            SELECT t.*, COUNT(ts.id) as stop_count,
                   SUM(trs.distance_km) as total_km
            FROM trips t
            LEFT JOIN trip_stops ts ON ts.trip_id = t.id
            LEFT JOIN trip_route_segments trs ON trs.trip_id = t.id
            GROUP BY t.id
            ORDER BY FIELD(t.status, "ongoing", "planned", "finished"), t.start_date DESC
        ');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM trips WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM trips WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO trips (slug, title, intro_text, status, created_by, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['intro_text'] ?? null,
            $data['status'] ?? 'planned',
            $data['created_by'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE trips SET title = ?, intro_text = ?, status = ?,
                start_date = ?, end_date = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['intro_text'] ?? null,
            $data['status'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM trips WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function summary(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(ts.id) as stop_count,
                   COALESCE(SUM(trs.distance_km), 0) as total_km,
                   COALESCE(SUM(trs.provider_eta_minutes), 0) as total_eta_provider,
                   COALESCE(SUM(trs.eta_95_minutes), 0) as total_eta_95
            FROM trips t
            LEFT JOIN trip_stops ts ON ts.trip_id = t.id
            LEFT JOIN trip_route_segments trs ON trs.trip_id = t.id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function generateSlug(string $title): string
    {
        $slug = mb_strtolower($title);
        $slug = strtr($slug, ['å' => 'a', 'ä' => 'a', 'ö' => 'o', 'é' => 'e', 'è' => 'e', 'ü' => 'u']);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}
```

- [ ] **Step 2: Create `app/Models/TripStop.php`**

```php
<?php

declare(strict_types=1);

class TripStop
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByTrip(int $tripId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ts.*, p.name as place_name, p.slug as place_slug,
                   p.lat, p.lng, p.place_type, p.country_code
            FROM trip_stops ts
            JOIN places p ON p.id = ts.place_id
            WHERE ts.trip_id = ?
            ORDER BY ts.stop_order ASC
        ');
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ts.*, p.name as place_name, p.slug as place_slug,
                   p.lat, p.lng, p.place_type
            FROM trip_stops ts
            JOIN places p ON p.id = ts.place_id
            WHERE ts.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function add(int $tripId, int $placeId, ?string $stopType, ?string $note): int
    {
        // Get next order position
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(stop_order), 0) + 1 FROM trip_stops WHERE trip_id = ?');
        $stmt->execute([$tripId]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('
            INSERT INTO trip_stops (trip_id, place_id, stop_order, stop_type, note)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$tripId, $placeId, $nextOrder, $stopType, $note]);
        return (int) $this->pdo->lastInsertId();
    }

    public function remove(int $id): void
    {
        $stop = $this->findById($id);
        if (!$stop) return;

        $this->pdo->prepare('DELETE FROM trip_stops WHERE id = ?')->execute([$id]);

        // Re-number remaining stops
        $stmt = $this->pdo->prepare('
            SELECT id FROM trip_stops WHERE trip_id = ? ORDER BY stop_order ASC
        ');
        $stmt->execute([$stop['trip_id']]);
        $remaining = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($remaining as $i => $stopId) {
            $this->pdo->prepare('UPDATE trip_stops SET stop_order = ? WHERE id = ?')
                ->execute([$i + 1, $stopId]);
        }
    }

    public function reorder(int $tripId, array $stopIds): void
    {
        foreach ($stopIds as $order => $stopId) {
            $this->pdo->prepare('UPDATE trip_stops SET stop_order = ? WHERE id = ? AND trip_id = ?')
                ->execute([$order + 1, $stopId, $tripId]);
        }
    }
}
```

- [ ] **Step 3: Create `app/Models/TripRouteSegment.php`**

```php
<?php

declare(strict_types=1);

class TripRouteSegment
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByTrip(int $tripId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT trs.*,
                   fs.place_id as from_place_id, fp.name as from_name, fp.lat as from_lat, fp.lng as from_lng,
                   tos.place_id as to_place_id, tp.name as to_name, tp.lat as to_lat, tp.lng as to_lng
            FROM trip_route_segments trs
            JOIN trip_stops fs ON fs.id = trs.from_stop_id
            JOIN places fp ON fp.id = fs.place_id
            JOIN trip_stops tos ON tos.id = trs.to_stop_id
            JOIN places tp ON tp.id = tos.place_id
            WHERE trs.trip_id = ?
            ORDER BY fs.stop_order ASC
        ');
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public function saveSegment(int $tripId, int $fromStopId, int $toStopId, array $routeData): void
    {
        // Delete existing segment for this pair
        $this->pdo->prepare('
            DELETE FROM trip_route_segments WHERE trip_id = ? AND from_stop_id = ? AND to_stop_id = ?
        ')->execute([$tripId, $fromStopId, $toStopId]);

        $eta95 = $routeData['distance_km'] > 0
            ? (int) round($routeData['distance_km'] / 95 * 60)
            : 0;

        $stmt = $this->pdo->prepare('
            INSERT INTO trip_route_segments
                (trip_id, from_stop_id, to_stop_id, distance_km, provider_eta_minutes, eta_95_minutes, geometry, provider_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $tripId,
            $fromStopId,
            $toStopId,
            $routeData['distance_km'],
            $routeData['provider_eta_minutes'],
            $eta95,
            $routeData['geometry'] ?? null,
            $routeData['provider_name'] ?? 'unknown',
        ]);
    }

    public function deleteByTrip(int $tripId): void
    {
        $this->pdo->prepare('DELETE FROM trip_route_segments WHERE trip_id = ?')->execute([$tripId]);
    }
}
```

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l app/Models/Trip.php && php -l app/Models/TripStop.php && php -l app/Models/TripRouteSegment.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/Trip.php app/Models/TripStop.php app/Models/TripRouteSegment.php
git commit -m "feat: Trip, TripStop, TripRouteSegment models"
```

---

## Task 3: Routing provider abstraction

**Files:**
- Create: `app/Services/Routing/RouteProviderInterface.php`
- Create: `app/Services/Routing/OpenRouteServiceProvider.php`
- Create: `app/Services/Routing/FakeRouteProvider.php`

- [ ] **Step 1: Create `app/Services/Routing/RouteProviderInterface.php`**

```php
<?php

declare(strict_types=1);

interface RouteProviderInterface
{
    /**
     * Get road route between two coordinates.
     *
     * @return array{distance_km: float, provider_eta_minutes: int, geometry: ?string, provider_name: string}
     */
    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array;
}
```

- [ ] **Step 2: Create `app/Services/Routing/FakeRouteProvider.php`**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/RouteProviderInterface.php';

class FakeRouteProvider implements RouteProviderInterface
{
    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        // Haversine straight-line distance, multiplied by 1.3 for road approximation
        $earthRadius = 6371;
        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;
        $straightLine = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
        $roadDistance = round($straightLine * 1.3, 2);

        // Assume average 70 km/h for provider ETA
        $etaMinutes = (int) round($roadDistance / 70 * 60);

        return [
            'distance_km'        => $roadDistance,
            'provider_eta_minutes' => $etaMinutes,
            'geometry'           => null,
            'provider_name'      => 'fake',
        ];
    }
}
```

- [ ] **Step 3: Create `app/Services/Routing/OpenRouteServiceProvider.php`**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/RouteProviderInterface.php';

class OpenRouteServiceProvider implements RouteProviderInterface
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getRoute(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $url = 'https://api.openrouteservice.org/v2/directions/driving-car';

        $body = json_encode([
            'coordinates' => [
                [$fromLng, $fromLat],  // ORS uses [lng, lat] order
                [$toLng, $toLat],
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json, application/geo+json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            // Fallback to fake provider on error
            require_once __DIR__ . '/FakeRouteProvider.php';
            $fake = new FakeRouteProvider();
            return $fake->getRoute($fromLat, $fromLng, $toLat, $toLng);
        }

        $data = json_decode($response, true);

        if (empty($data['routes'][0])) {
            require_once __DIR__ . '/FakeRouteProvider.php';
            $fake = new FakeRouteProvider();
            return $fake->getRoute($fromLat, $fromLng, $toLat, $toLng);
        }

        $route = $data['routes'][0];
        $summary = $route['summary'];

        return [
            'distance_km'         => round($summary['distance'] / 1000, 2),
            'provider_eta_minutes' => (int) round($summary['duration'] / 60),
            'geometry'            => $route['geometry'] ?? null,
            'provider_name'       => 'openrouteservice',
        ];
    }
}
```

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l app/Services/Routing/RouteProviderInterface.php && php -l app/Services/Routing/FakeRouteProvider.php && php -l app/Services/Routing/OpenRouteServiceProvider.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Services/Routing/
git commit -m "feat: routing provider abstraction with ORS and fake provider"
```

---

## Task 4: GPX trip exporter

**Files:**
- Create: `app/Services/Export/GpxTripExporter.php`

- [ ] **Step 1: Create the exporter**

```php
<?php

declare(strict_types=1);

class GpxTripExporter
{
    /**
     * Generate GPX XML for a trip with ordered stops.
     *
     * @param array $trip Trip data
     * @param array $stops Array of stops with place_name, lat, lng, stop_order
     * @return string GPX XML content
     */
    public function export(array $trip, array $stops): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('gpx');
        $xml->writeAttribute('version', '1.1');
        $xml->writeAttribute('creator', 'Frizon.org');
        $xml->writeAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xsi:schemaLocation', 'http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd');

        // Metadata
        $xml->startElement('metadata');
        $xml->writeElement('name', $trip['title']);
        if (!empty($trip['intro_text'])) {
            $xml->writeElement('desc', $trip['intro_text']);
        }
        $xml->writeElement('author', 'Frizon of Sweden');
        $xml->writeElement('time', date('c'));
        $xml->endElement(); // metadata

        // Waypoints for each stop
        foreach ($stops as $stop) {
            $xml->startElement('wpt');
            $xml->writeAttribute('lat', (string) $stop['lat']);
            $xml->writeAttribute('lon', (string) $stop['lng']);
            $xml->writeElement('name', $stop['place_name']);
            if (!empty($stop['note'])) {
                $xml->writeElement('desc', $stop['note']);
            }
            $xml->writeElement('sym', 'Flag');
            $xml->endElement(); // wpt
        }

        // Route with ordered route points
        $xml->startElement('rte');
        $xml->writeElement('name', $trip['title']);
        foreach ($stops as $stop) {
            $xml->startElement('rtept');
            $xml->writeAttribute('lat', (string) $stop['lat']);
            $xml->writeAttribute('lon', (string) $stop['lng']);
            $xml->writeElement('name', $stop['place_name']);
            $xml->endElement(); // rtept
        }
        $xml->endElement(); // rte

        $xml->endElement(); // gpx
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Send GPX as a downloadable file.
     */
    public function download(array $trip, array $stops): void
    {
        $gpx = $this->export($trip, $stops);
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $trip['title']) . '.gpx';

        header('Content-Type: application/gpx+xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($gpx));
        echo $gpx;
        exit;
    }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l app/Services/Export/GpxTripExporter.php
```

- [ ] **Step 3: Commit**

```bash
git add app/Services/Export/GpxTripExporter.php
git commit -m "feat: GPX trip exporter with waypoints and route for Garmin"
```

---

## Task 5: Trip controller

**Files:**
- Create: `app/Controllers/TripController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create `app/Controllers/TripController.php`**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/Trip.php';
require_once dirname(__DIR__) . '/Models/TripStop.php';
require_once dirname(__DIR__) . '/Models/TripRouteSegment.php';
require_once dirname(__DIR__) . '/Models/Place.php';
require_once dirname(__DIR__) . '/Services/Export/GpxTripExporter.php';

class TripController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function index(array $params): void
    {
        Auth::requireLogin();
        $tripModel = new Trip($this->pdo);
        $trips = $tripModel->all();

        $grouped = ['ongoing' => [], 'planned' => [], 'finished' => []];
        foreach ($trips as $trip) {
            $grouped[$trip['status']][] = $trip;
        }

        $pageTitle = 'Resor';
        view('trips/index', compact('grouped', 'pageTitle'));
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $pageTitle = 'Ny resa';
        view('trips/create', compact('pageTitle'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Resenamn krävs.');
            redirect('/resor/ny');
        }

        $tripModel = new Trip($this->pdo);
        $tripModel->create([
            'slug'       => Trip::generateSlug($title),
            'title'      => $title,
            'intro_text' => trim($_POST['intro_text'] ?? '') ?: null,
            'status'     => $_POST['status'] ?? 'planned',
            'created_by' => Auth::userId(),
            'start_date' => $_POST['start_date'] ?: null,
            'end_date'   => $_POST['end_date'] ?: null,
        ]);

        flash('success', 'Resan har skapats!');
        redirect('/resor');
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); echo '<h1>Resan hittades inte</h1>'; return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $segmentModel = new TripRouteSegment($this->pdo);
        $segments = $segmentModel->findByTrip((int) $trip['id']);

        $summary = $tripModel->summary((int) $trip['id']);

        // All places for "add stop" dropdown
        $placeModel = new Place($this->pdo);
        $allPlaces = $placeModel->all();

        $pageTitle = $trip['title'];
        view('trips/show', compact('trip', 'stops', 'segments', 'summary', 'allPlaces', 'pageTitle'));
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $pageTitle = 'Redigera ' . $trip['title'];
        view('trips/edit', compact('trip', 'pageTitle'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $tripModel->update((int) $trip['id'], [
            'title'      => trim($_POST['title'] ?? $trip['title']),
            'intro_text' => trim($_POST['intro_text'] ?? '') ?: null,
            'status'     => $_POST['status'] ?? $trip['status'],
            'start_date' => $_POST['start_date'] ?: null,
            'end_date'   => $_POST['end_date'] ?: null,
        ]);

        flash('success', 'Resan har uppdaterats.');
        redirect('/resor/' . $params['slug']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if ($trip) {
            $tripModel->delete((int) $trip['id']);
            flash('success', 'Resan har tagits bort.');
        }
        redirect('/resor');
    }

    public function addStop(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $placeId = (int) ($_POST['place_id'] ?? 0);
        if ($placeId === 0) {
            flash('error', 'Välj en plats.');
            redirect('/resor/' . $params['slug']);
        }

        $stopModel = new TripStop($this->pdo);
        $stopModel->add(
            (int) $trip['id'],
            $placeId,
            $_POST['stop_type'] ?? null,
            trim($_POST['note'] ?? '') ?: null
        );

        flash('success', 'Hållplats tillagd!');
        redirect('/resor/' . $params['slug']);
    }

    public function removeStop(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $stopModel = new TripStop($this->pdo);
        $stop = $stopModel->findById((int) $params['stopId']);
        if (!$stop) { redirect('/resor'); return; }

        // Find trip slug for redirect
        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findById((int) $stop['trip_id']);

        $stopModel->remove((int) $params['stopId']);

        flash('success', 'Hållplatsen har tagits bort.');
        redirect('/resor/' . ($trip['slug'] ?? ''));
    }

    public function reorderStops(array $params): void
    {
        Auth::requireLogin();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $input = json_decode(file_get_contents('php://input'), true);
        $stopIds = $input['stop_ids'] ?? [];

        if (!empty($stopIds)) {
            $stopModel = new TripStop($this->pdo);
            $stopModel->reorder((int) $trip['id'], $stopIds);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function calculateRoute(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        if (count($stops) < 2) {
            flash('error', 'Minst två hållplatser krävs för att beräkna rutt.');
            redirect('/resor/' . $params['slug']);
        }

        // Get route provider
        $provider = $this->getRouteProvider();

        $segmentModel = new TripRouteSegment($this->pdo);
        $segmentModel->deleteByTrip((int) $trip['id']);

        for ($i = 0; $i < count($stops) - 1; $i++) {
            $from = $stops[$i];
            $to = $stops[$i + 1];

            $routeData = $provider->getRoute(
                (float) $from['lat'], (float) $from['lng'],
                (float) $to['lat'], (float) $to['lng']
            );

            $segmentModel->saveSegment(
                (int) $trip['id'],
                (int) $from['id'],
                (int) $to['id'],
                $routeData
            );
        }

        flash('success', 'Rutten har beräknats!');
        redirect('/resor/' . $params['slug']);
    }

    public function exportGpx(array $params): void
    {
        Auth::requireLogin();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $exporter = new GpxTripExporter();
        $exporter->download($trip, $stops);
    }

    private function getRouteProvider(): RouteProviderInterface
    {
        $apiKey = $_ENV['ORS_API_KEY'] ?? '';

        if ($apiKey !== '') {
            require_once dirname(__DIR__) . '/Services/Routing/OpenRouteServiceProvider.php';
            return new OpenRouteServiceProvider($apiKey);
        }

        require_once dirname(__DIR__) . '/Services/Routing/FakeRouteProvider.php';
        return new FakeRouteProvider();
    }
}
```

- [ ] **Step 2: Add trip routes to `routes/web.php`**

Add these routes inside `registerRoutes()` after the Visit API routes:

```php
    // Trips
    $router->get('/resor', 'TripController', 'index');
    $router->get('/resor/ny', 'TripController', 'create');
    $router->post('/resor', 'TripController', 'store');
    $router->get('/resor/{slug}', 'TripController', 'show');
    $router->get('/resor/{slug}/redigera', 'TripController', 'edit');
    $router->put('/resor/{slug}', 'TripController', 'update');
    $router->delete('/resor/{slug}', 'TripController', 'destroy');

    // Trip stops
    $router->post('/resor/{slug}/hallplatser', 'TripController', 'addStop');
    $router->delete('/resor/hallplatser/{stopId}', 'TripController', 'removeStop');
    $router->put('/resor/{slug}/hallplatser/ordning', 'TripController', 'reorderStops');

    // Trip routing and export
    $router->post('/resor/{slug}/berakna-rutt', 'TripController', 'calculateRoute');
    $router->get('/resor/{slug}/export/gpx', 'TripController', 'exportGpx');
```

- [ ] **Step 3: Verify PHP syntax**

```bash
php -l app/Controllers/TripController.php && php -l routes/web.php
```

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/TripController.php routes/web.php
git commit -m "feat: trip controller with CRUD, stops, routing, GPX export"
```

---

## Task 6: Trip views and CSS

**Files:**
- Create: `views/trips/index.php`
- Create: `views/trips/create.php`
- Create: `views/trips/show.php`
- Create: `views/trips/edit.php`
- Create: `views/partials/trip-card.php`
- Create: `views/partials/stop-card.php`
- Create: `public/css/pages/trips.css`
- Modify: `public/css/main.css`

- [ ] **Step 1: Create `views/partials/trip-card.php`**

```php
<?php
$statusLabels = ['planned' => 'Planerad', 'ongoing' => 'Pågående', 'finished' => 'Avslutad'];
$statusLabel = $statusLabels[$trip['status']] ?? $trip['status'];
$stopCount = $trip['stop_count'] ?? 0;
$totalKm = $trip['total_km'] ? number_format((float) $trip['total_km'], 0) : '–';
?>
<a href="/resor/<?= htmlspecialchars($trip['slug']) ?>" class="trip-card">
    <div class="trip-card__header">
        <span class="trip-card__title"><?= htmlspecialchars($trip['title']) ?></span>
        <span class="trip-card__status trip-card__status--<?= htmlspecialchars($trip['status']) ?>"><?= $statusLabel ?></span>
    </div>
    <div class="trip-card__meta">
        <?php if ($trip['start_date']): ?>
            <span><?= htmlspecialchars($trip['start_date']) ?></span>
            <?php if ($trip['end_date']): ?> → <span><?= htmlspecialchars($trip['end_date']) ?></span><?php endif; ?>
            <span class="trip-card__sep">·</span>
        <?php endif; ?>
        <span><?= $stopCount ?> hållplatser</span>
        <span class="trip-card__sep">·</span>
        <span><?= $totalKm ?> km</span>
    </div>
</a>
```

- [ ] **Step 2: Create `views/partials/stop-card.php`**

```php
<?php
$stopTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Vildcamping','camping'=>'Camping',
];
$typeLabel = $stop['stop_type'] ? ($stopTypes[$stop['stop_type']] ?? $stop['stop_type']) : ($stopTypes[$stop['place_type']] ?? '');
?>
<div class="stop-card" data-stop-id="<?= $stop['id'] ?>">
    <div class="stop-card__order"><?= $stop['stop_order'] ?></div>
    <div class="stop-card__body">
        <div class="stop-card__header">
            <a href="/platser/<?= htmlspecialchars($stop['place_slug']) ?>" class="stop-card__name"><?= htmlspecialchars($stop['place_name']) ?></a>
            <?php if ($typeLabel): ?>
                <span class="stop-card__type"><?= htmlspecialchars($typeLabel) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($stop['note']): ?>
            <p class="stop-card__note"><?= htmlspecialchars($stop['note']) ?></p>
        <?php endif; ?>
    </div>
    <form method="POST" action="/resor/hallplatser/<?= $stop['id'] ?>" class="stop-card__actions">
        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="btn-ghost btn--sm" onclick="return confirm('Ta bort hållplatsen?')" aria-label="Ta bort">×</button>
    </form>
</div>
```

- [ ] **Step 3: Create `views/trips/index.php`**

```php
<?php
$statusLabels = ['ongoing' => 'Pågående', 'planned' => 'Planerade', 'finished' => 'Avslutade'];
$hasTrips = !empty($grouped['ongoing']) || !empty($grouped['planned']) || !empty($grouped['finished']);
?>

<?php if (!$hasTrips): ?>
    <div class="empty-state">
        <p class="text-muted">Inga resor ännu.</p>
        <a href="/resor/ny" class="btn btn-primary mt-4">Skapa din första resa</a>
    </div>
<?php else: ?>
    <?php foreach (['ongoing', 'planned', 'finished'] as $status): ?>
        <?php if (!empty($grouped[$status])): ?>
            <div class="status-group status-group--<?= $status ?> mb-6">
                <div class="status-group__header">
                    <span class="status-group__label"><?= $statusLabels[$status] ?></span>
                    <span class="status-group__accent"></span>
                </div>
                <?php foreach ($grouped[$status] as $trip): ?>
                    <?php include dirname(__DIR__) . '/partials/trip-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
```

- [ ] **Step 4: Create `views/trips/create.php`**

```php
<div class="page-header mb-4">
    <a href="/resor" class="btn-ghost btn--sm">&larr; Resor</a>
</div>

<form method="POST" action="/resor" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="title" class="form-label">Resenamn *</label>
        <input type="text" id="title" name="title" class="form-input" required placeholder='t.ex. "Normandie 2026"'>
    </div>

    <div class="form-group">
        <label class="form-label">Datum</label>
        <div class="flex gap-2">
            <input type="date" name="start_date" class="form-input" style="flex:1;">
            <span style="align-self:center;">→</span>
            <input type="date" name="end_date" class="form-input" style="flex:1;">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Status</label>
        <div class="flex gap-2">
            <?php foreach (['planned'=>'Planerad','ongoing'=>'Pågående','finished'=>'Avslutad'] as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="status" value="<?= $val ?>" <?= $val === 'planned' ? 'checked' : '' ?>>
                    <span class="chip"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="intro_text" class="form-label">Intro (valfritt)</label>
        <textarea id="intro_text" name="intro_text" class="form-textarea" rows="3" placeholder="Kort beskrivning av resan..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn--full">Skapa resa</button>
</form>
```

- [ ] **Step 5: Create `views/trips/show.php`**

```php
<div class="page-header mb-4">
    <a href="/resor" class="btn-ghost btn--sm">&larr; Resor</a>
</div>

<div class="trip-detail">
    <h1 class="mb-2"><?= htmlspecialchars($trip['title']) ?></h1>

    <div class="trip-detail__meta text-sm text-muted mb-4">
        <?php
        $statusLabels = ['planned'=>'Planerad','ongoing'=>'Pågående','finished'=>'Avslutad'];
        ?>
        <span class="trip-card__status trip-card__status--<?= $trip['status'] ?>"><?= $statusLabels[$trip['status']] ?></span>
        <?php if ($trip['start_date']): ?>
            · <?= htmlspecialchars($trip['start_date']) ?>
            <?php if ($trip['end_date']): ?> → <?= htmlspecialchars($trip['end_date']) ?><?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($trip['intro_text']): ?>
        <p class="mb-4"><?= nl2br(htmlspecialchars($trip['intro_text'])) ?></p>
    <?php endif; ?>

    <!-- Route map -->
    <?php if (!empty($stops) && count($stops) >= 2): ?>
        <div id="trip-map" class="trip-detail__map mb-4"
             data-stops='<?= htmlspecialchars(json_encode(array_map(fn($s) => ['lat' => (float)$s['lat'], 'lng' => (float)$s['lng'], 'name' => $s['place_name']], $stops))) ?>'>
        </div>
    <?php endif; ?>

    <!-- Summary -->
    <?php if ($summary['stop_count'] > 0): ?>
        <div class="trip-summary mb-4">
            <span><?= $summary['stop_count'] ?> hållplatser</span>
            <?php if ($summary['total_km'] > 0): ?>
                · <span><?= number_format((float) $summary['total_km'], 0) ?> km</span>
                · <span><?= $this->formatMinutes((int) $summary['total_eta_95']) ?> (95 km/h)</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Stops -->
    <div class="trip-detail__section mb-6">
        <div class="flex-between mb-4">
            <h3>Hållplatser (<?= count($stops) ?>)</h3>
            <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>/redigera" class="btn btn-ghost btn--sm">Redigera</a>
        </div>

        <?php if (empty($stops)): ?>
            <p class="text-muted text-sm">Inga hållplatser ännu.</p>
        <?php else: ?>
            <div class="stop-list" id="stop-list">
                <?php foreach ($stops as $i => $stop): ?>
                    <?php include dirname(__DIR__) . '/partials/stop-card.php'; ?>

                    <?php
                    // Show segment between stops
                    if ($i < count($stops) - 1) {
                        foreach ($segments as $seg) {
                            if ((int)$seg['from_stop_id'] === (int)$stop['id']) {
                                echo '<div class="route-segment">';
                                echo '<span class="route-segment__line"></span>';
                                echo '<span class="route-segment__pill">';
                                echo number_format((float)$seg['distance_km'], 0) . ' km · ';
                                echo $seg['eta_95_minutes'] . ' min';
                                echo '</span>';
                                echo '</div>';
                                break;
                            }
                        }
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add stop form -->
        <form method="POST" action="/resor/<?= htmlspecialchars($trip['slug']) ?>/hallplatser" class="mt-4">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <div class="flex gap-2">
                <select name="place_id" class="form-select" required style="flex:1;">
                    <option value="">Välj plats...</option>
                    <?php foreach ($allPlaces as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['country_code'] ?? '?' ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn--sm">+ Lägg till</button>
            </div>
        </form>
    </div>

    <!-- Actions -->
    <div class="trip-detail__actions flex gap-3 mb-4">
        <?php if (count($stops) >= 2): ?>
            <form method="POST" action="/resor/<?= htmlspecialchars($trip['slug']) ?>/berakna-rutt" style="display:inline;">
                <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                <button type="submit" class="btn btn-secondary btn--sm">Beräkna rutt</button>
            </form>
            <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>/export/gpx" class="btn btn-secondary btn--sm">Exportera GPX</a>
        <?php endif; ?>
        <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>/redigera" class="btn btn-ghost btn--sm">Redigera resa</a>
    </div>
</div>

<script src="/js/trips.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('trip-map');
    if (mapEl && mapEl.dataset.stops) {
        initTripMap(mapEl, JSON.parse(mapEl.dataset.stops));
    }
});
</script>
```

Note: The `$this->formatMinutes()` call won't work in a template. Add a helper function instead. Create a small formatting helper:

Replace `$this->formatMinutes((int) $summary['total_eta_95'])` with inline PHP:
```php
<?php
$totalMin = (int) $summary['total_eta_95'];
$h = intdiv($totalMin, 60);
$m = $totalMin % 60;
echo $h > 0 ? "{$h} tim {$m} min" : "{$m} min";
?>
```

- [ ] **Step 6: Create `views/trips/edit.php`**

```php
<div class="page-header mb-4">
    <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera resa</h2>
</div>

<form method="POST" action="/resor/<?= htmlspecialchars($trip['slug']) ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="form-group">
        <label for="title" class="form-label">Resenamn *</label>
        <input type="text" id="title" name="title" class="form-input" required value="<?= htmlspecialchars($trip['title']) ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Datum</label>
        <div class="flex gap-2">
            <input type="date" name="start_date" class="form-input" style="flex:1;" value="<?= htmlspecialchars($trip['start_date'] ?? '') ?>">
            <span style="align-self:center;">→</span>
            <input type="date" name="end_date" class="form-input" style="flex:1;" value="<?= htmlspecialchars($trip['end_date'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Status</label>
        <div class="flex gap-2">
            <?php foreach (['planned'=>'Planerad','ongoing'=>'Pågående','finished'=>'Avslutad'] as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="status" value="<?= $val ?>" <?= $trip['status'] === $val ? 'checked' : '' ?>>
                    <span class="chip"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="intro_text" class="form-label">Intro</label>
        <textarea id="intro_text" name="intro_text" class="form-textarea" rows="3"><?= htmlspecialchars($trip['intro_text'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/resor/<?= htmlspecialchars($trip['slug']) ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Är du säker? Alla hållplatser och ruttdata tas bort.')">Ta bort resa</button>
</form>
```

- [ ] **Step 7: Create `public/css/pages/trips.css`**

```css
/* Trip card */
.trip-card {
  display: block;
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  margin-bottom: var(--space-3);
  text-decoration: none;
  transition: box-shadow var(--transition-fast);
}

.trip-card:hover {
  box-shadow: var(--shadow-md);
}

.trip-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-2);
}

.trip-card__title {
  font-size: var(--text-lg);
  font-weight: var(--weight-semibold);
  color: var(--color-text);
}

.trip-card__status {
  font-size: var(--text-xs);
  font-weight: var(--weight-semibold);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}

.trip-card__status--ongoing { background: var(--color-info-bg); color: var(--color-accent); }
.trip-card__status--planned { background: var(--color-warning-bg); color: var(--color-warning); }
.trip-card__status--finished { background: var(--color-bg); color: var(--color-text-muted); }

.trip-card__meta {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}

.trip-card__sep { margin: 0 var(--space-1); }

/* Trip detail */
.trip-detail__map {
  width: 100%;
  height: 200px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.trip-summary {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  padding: var(--space-3);
  background: var(--color-bg);
  border-radius: var(--radius-md);
}

/* Stop card */
.stop-card {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.stop-card__order {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--color-accent);
  color: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-sm);
  font-weight: var(--weight-bold);
  flex-shrink: 0;
}

.stop-card__body { flex: 1; min-width: 0; }

.stop-card__header {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.stop-card__name {
  font-weight: var(--weight-semibold);
  color: var(--color-text);
  text-decoration: none;
}

.stop-card__name:hover { color: var(--color-accent); }

.stop-card__type {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.stop-card__note {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-top: 2px;
}

/* Stop list with vertical connectors */
.stop-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Route segment between stops */
.route-segment {
  display: flex;
  align-items: center;
  padding: var(--space-1) 0 var(--space-1) 14px;
}

.route-segment__line {
  width: 2px;
  height: 24px;
  background: var(--color-border);
  border-style: dashed;
  margin-right: var(--space-3);
}

.route-segment__pill {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  padding: 2px 10px;
}
```

- [ ] **Step 8: Add trips.css to `public/css/main.css`**

Add after the `places.css` import:
```css
@import url('pages/trips.css');
```

- [ ] **Step 9: Verify PHP syntax**

```bash
php -l views/trips/index.php && php -l views/trips/create.php && php -l views/trips/show.php && php -l views/trips/edit.php && php -l views/partials/trip-card.php && php -l views/partials/stop-card.php
```

- [ ] **Step 10: Commit**

```bash
git add views/trips/ views/partials/trip-card.php views/partials/stop-card.php public/css/pages/trips.css public/css/main.css
git commit -m "feat: trip views — index, create, show, edit with stops and route segments"
```

---

## Task 7: Trip map and reorder JavaScript

**Files:**
- Create: `public/js/trips.js`

- [ ] **Step 1: Create `public/js/trips.js`**

```javascript
// Trip route map
function initTripMap(el, stops) {
    if (!stops || stops.length === 0) return;

    var map = L.map(el);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var bounds = L.latLngBounds();

    stops.forEach(function(stop, i) {
        var marker = L.marker([stop.lat, stop.lng]).addTo(map);
        marker.bindPopup('<strong>' + (i + 1) + '. ' + stop.name + '</strong>');
        bounds.extend([stop.lat, stop.lng]);
    });

    // Draw line between stops
    if (stops.length >= 2) {
        var coords = stops.map(function(s) { return [s.lat, s.lng]; });
        L.polyline(coords, {
            color: '#3D7A87',
            weight: 3,
            opacity: 0.8,
            dashArray: '8 4'
        }).addTo(map);
    }

    map.fitBounds(bounds, { padding: [30, 30] });
}

// Stop reorder via drag (simple implementation)
document.addEventListener('DOMContentLoaded', function() {
    var list = document.getElementById('stop-list');
    if (!list) return;

    var dragItem = null;

    list.querySelectorAll('.stop-card').forEach(function(card) {
        card.draggable = true;

        card.addEventListener('dragstart', function(e) {
            dragItem = this;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', function() {
            this.style.opacity = '1';
            dragItem = null;
        });

        card.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        card.addEventListener('drop', function(e) {
            e.preventDefault();
            if (dragItem && dragItem !== this) {
                var cards = Array.from(list.querySelectorAll('.stop-card'));
                var fromIndex = cards.indexOf(dragItem);
                var toIndex = cards.indexOf(this);

                if (fromIndex < toIndex) {
                    this.parentNode.insertBefore(dragItem, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(dragItem, this);
                }

                saveStopOrder();
            }
        });
    });

    function saveStopOrder() {
        var cards = list.querySelectorAll('.stop-card');
        var stopIds = Array.from(cards).map(function(c) { return parseInt(c.dataset.stopId); });

        // Get trip slug from URL
        var slug = window.location.pathname.split('/resor/')[1];
        if (slug) slug = slug.split('/')[0];

        fetch('/resor/' + slug + '/hallplatser/ordning', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify({ stop_ids: stopIds })
        });
    }
});
```

- [ ] **Step 2: Commit**

```bash
git add public/js/trips.js
git commit -m "feat: trip map with polyline, drag-and-drop stop reordering"
```

---

## Task 8: Tests — ETA 95, stop ordering, GPX export

**Files:**
- Create: `tests/test_eta95.php`
- Create: `tests/test_stop_ordering.php`
- Create: `tests/test_gpx_export.php`

- [ ] **Step 1: Create `tests/test_eta95.php`**

```php
<?php
/**
 * Test: ETA 95 km/h calculation.
 * Formula: round(distance_km / 95 * 60)
 * Run: php tests/test_eta95.php
 */

$tests = [
    ['0 km',     0,       0],
    ['95 km',    95,      60],
    ['190 km',   190,     120],
    ['47.5 km',  47.5,    30],
    ['100 km',   100,     63],
    ['500 km',   500,     316],
    ['1.5 km',   1.5,     1],
];

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $distKm, $expectedMin]) {
    $result = (int) round($distKm / 95 * 60);
    $ok = $result === $expectedMin;

    if ($ok) {
        printf("PASS: %s → %d min\n", $name, $result);
        $passed++;
    } else {
        printf("FAIL: %s → got %d min, expected %d min\n", $name, $result, $expectedMin);
        $failed++;
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 2: Create `tests/test_gpx_export.php`**

```php
<?php
/**
 * Test: GPX export structure.
 * Run: php tests/test_gpx_export.php
 */

require_once dirname(__DIR__) . '/app/Services/Export/GpxTripExporter.php';

$trip = ['title' => 'Normandie 2026', 'intro_text' => 'Sommarresa med Frizze'];
$stops = [
    ['place_name' => 'Hammarö Ställplats', 'lat' => 59.3299, 'lng' => 13.5227, 'note' => 'Start'],
    ['place_name' => 'Camping Le Grand Large', 'lat' => 48.8400, 'lng' => -1.5050, 'note' => null],
    ['place_name' => 'Café Sjökanten', 'lat' => 58.7530, 'lng' => 17.0086, 'note' => 'Fika'],
];

$exporter = new GpxTripExporter();
$gpx = $exporter->export($trip, $stops);

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Parse XML
$xml = simplexml_load_string($gpx);
check('Valid XML', $xml !== false);
check('GPX version 1.1', (string) $xml['version'] === '1.1');
check('Creator is Frizon', (string) $xml['creator'] === 'Frizon.org');
check('Has metadata name', (string) $xml->metadata->name === 'Normandie 2026');
check('Has metadata desc', (string) $xml->metadata->desc === 'Sommarresa med Frizze');
check('Has 3 waypoints', count($xml->wpt) === 3);
check('First waypoint name', (string) $xml->wpt[0]->name === 'Hammarö Ställplats');
check('First waypoint lat', (string) $xml->wpt[0]['lat'] === '59.3299');
check('First waypoint lon', (string) $xml->wpt[0]['lon'] === '13.5227');
check('Has route element', isset($xml->rte));
check('Route has 3 points', count($xml->rte->rtept) === 3);
check('Route name matches', (string) $xml->rte->name === 'Normandie 2026');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 3: Create `tests/test_stop_ordering.php`**

```php
<?php
/**
 * Test: Stop ordering logic (unit test without database).
 * Run: php tests/test_stop_ordering.php
 */

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

// Simulate reorder: given stop_ids in new order, verify order assignment
function simulateReorder(array $stopIds): array {
    $result = [];
    foreach ($stopIds as $order => $id) {
        $result[$id] = $order + 1;
    }
    return $result;
}

// Test 1: Simple reorder
$result = simulateReorder([3, 1, 2]);
check('Reorder [3,1,2]: stop 3 is first', $result[3] === 1);
check('Reorder [3,1,2]: stop 1 is second', $result[1] === 2);
check('Reorder [3,1,2]: stop 2 is third', $result[2] === 3);

// Test 2: No change
$result = simulateReorder([1, 2, 3]);
check('No change: stop 1 stays first', $result[1] === 1);
check('No change: stop 3 stays third', $result[3] === 3);

// Test 3: Reverse
$result = simulateReorder([5, 4, 3, 2, 1]);
check('Reverse: stop 5 is first', $result[5] === 1);
check('Reverse: stop 1 is last', $result[1] === 5);

// Test 4: Single stop
$result = simulateReorder([42]);
check('Single stop: order is 1', $result[42] === 1);

// Test 5: After removal, renumbering
function simulateRemoveAndRenumber(array $stops, int $removeId): array {
    $remaining = array_values(array_filter($stops, fn($s) => $s['id'] !== $removeId));
    $result = [];
    foreach ($remaining as $i => $stop) {
        $result[$stop['id']] = $i + 1;
    }
    return $result;
}

$stops = [
    ['id' => 1, 'order' => 1],
    ['id' => 2, 'order' => 2],
    ['id' => 3, 'order' => 3],
];
$result = simulateRemoveAndRenumber($stops, 2);
check('Remove middle: stop 1 stays 1', $result[1] === 1);
check('Remove middle: stop 3 becomes 2', $result[3] === 2);
check('Remove middle: only 2 stops remain', count($result) === 2);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 4: Run all tests**

```bash
php tests/test_eta95.php && php tests/test_gpx_export.php && php tests/test_stop_ordering.php
```
Expected: All pass.

- [ ] **Step 5: Commit**

```bash
git add tests/test_eta95.php tests/test_gpx_export.php tests/test_stop_ordering.php
git commit -m "test: ETA 95 calculation, GPX export structure, stop ordering logic"
```

---

## Task 9: Update dashboard trip count

**Files:**
- Modify: `app/Controllers/DashboardController.php`
- Modify: `views/dashboard/index.php`

- [ ] **Step 1: Update DashboardController to count trips**

Add after the countries stat:
```php
$stats['trips'] = (int) $this->pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn();
```

- [ ] **Step 2: Update dashboard view**

Replace the hardcoded `0` for Resor with `$stats['trips']`.

- [ ] **Step 3: Commit**

```bash
git add app/Controllers/DashboardController.php views/dashboard/index.php
git commit -m "feat: show real trip count on dashboard"
```

---

## Summary

Phase 2 delivers:
- **3 new database tables**: trips, trip_stops, trip_route_segments
- **3 new models**: Trip, TripStop, TripRouteSegment
- **Routing abstraction**: Interface + OpenRouteService + Fake provider
- **GPX export**: Garmin-compatible with waypoints and route
- **Trip CRUD**: Create, edit, delete trips with status (planned/ongoing/finished)
- **Stop management**: Add, remove, reorder stops (drag-and-drop)
- **Route calculation**: Per-segment distance, provider ETA, 95 km/h ETA
- **Trip views**: Index (grouped by status), detail with map and stops, create, edit
- **3 test scripts**: ETA 95, GPX structure, stop ordering
- **Dashboard**: Real trip count
