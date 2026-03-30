# Phase 1 — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the project skeleton, auth, database schema, place/visit CRUD, image upload, and GPS place capture — the foundation everything else builds on.

**Architecture:** Custom MVC-ish PHP 8 app. `public/index.php` as front controller, simple router dispatching to controllers, PDO for database, PHP templates for views, session auth. Mobile-first UI using the Frizon design system (see `docs/UI-DESIGN.md`).

**Tech Stack:** PHP 8.x, MySQL/MariaDB, PDO, Leaflet.js, plain CSS (custom properties), plain JS, LiteSpeed.

**Reference docs:**
- `docs/SPEC.md` — full product spec
- `docs/UI-DESIGN.md` — complete design system (colors, components, layouts)
- `docs/PHASES.md` — phase breakdown
- `CLAUDE.md` — coding rules and clarifications

---

## File Structure

```
frizon.org/
├── public/
│   ├── index.php              # Front controller
│   ├── .htaccess              # LiteSpeed URL rewriting
│   ├── css/
│   │   ├── variables.css      # CSS custom properties (from UI-DESIGN.md 7.1)
│   │   ├── reset.css          # Minimal CSS reset
│   │   ├── base.css           # Body, typography, links
│   │   ├── layout.css         # Page structure, sidebar, header, navbar
│   │   ├── components/
│   │   │   ├── buttons.css
│   │   │   ├── cards.css
│   │   │   ├── forms.css
│   │   │   ├── modals.css
│   │   │   ├── navigation.css
│   │   │   ├── ratings.css
│   │   │   ├── tags.css
│   │   │   ├── map.css
│   │   │   ├── toast.css
│   │   │   ├── skeleton.css
│   │   │   └── gallery.css
│   │   ├── pages/
│   │   │   ├── auth.css
│   │   │   ├── dashboard.css
│   │   │   └── places.css
│   │   ├── utilities.css
│   │   └── main.css           # Imports all in order
│   ├── js/
│   │   ├── app.js             # Global: CSRF, toast, utils
│   │   ├── map.js             # Leaflet helpers, markers
│   │   ├── gps.js             # Geolocation + nearby detection
│   │   ├── gallery.js         # Image upload + preview
│   │   ├── ratings.js         # Rating input widget
│   │   └── tags.js            # Tag autocomplete input
│   └── img/
│       └── frizon-logo.png    # Copy of logo for web use
├── app/
│   ├── bootstrap.php          # Load .env, PDO, session, constants
│   ├── Router.php             # Simple regex router
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── PlaceController.php
│   │   └── VisitController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Place.php
│   │   ├── Visit.php
│   │   ├── VisitRating.php
│   │   └── VisitImage.php
│   ├── Services/
│   │   ├── Auth.php           # Session auth service
│   │   ├── ImageService.php   # Upload, resize, delete
│   │   └── CsrfService.php   # CSRF token generation/validation
│   ├── Middleware/
│   │   └── AuthMiddleware.php # Require login check
│   └── Helpers/
│       ├── view.php           # Template render helper
│       ├── redirect.php       # Redirect helper
│       └── flash.php          # Flash message helper
├── views/
│   ├── layouts/
│   │   ├── app.php            # Private layout (header, nav, footer)
│   │   └── auth.php           # Auth layout (centered card)
│   ├── partials/
│   │   ├── header.php
│   │   ├── nav-mobile.php     # Bottom navbar
│   │   ├── nav-desktop.php    # Sidebar
│   │   ├── toast.php
│   │   ├── csrf-field.php
│   │   ├── place-card.php
│   │   ├── visit-card.php
│   │   └── rating-display.php
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   └── index.php
│   ├── places/
│   │   ├── index.php
│   │   ├── show.php
│   │   ├── create.php         # GPS quick-add form
│   │   └── edit.php
│   └── visits/
│       ├── create.php
│       ├── show.php
│       └── edit.php
├── config/
│   └── app.php                # Config loaded from .env
├── database/
│   ├── migrations/
│   │   └── 001_initial_schema.sql
│   └── seed.sql               # Test data
├── storage/
│   └── uploads/
│       ├── originals/         # Original uploaded images
│       ├── thumbnails/        # 150x150
│       ├── cards/             # 400x300
│       └── detail/            # 1200x900
├── routes/
│   └── web.php                # Route definitions
├── tests/
│   └── test_place_radius.php  # Place detection radius test
├── .env.example
├── .gitignore
└── README.md
```

---

## Task 1: Project skeleton and configuration

**Files:**
- Create: `public/index.php`
- Create: `public/.htaccess`
- Create: `app/bootstrap.php`
- Create: `config/app.php`
- Create: `.env.example`
- Create: `.gitignore`
- Create: `README.md`

- [ ] **Step 1: Create `.env.example`**

```ini
APP_NAME=Frizon
APP_URL=http://localhost
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=frizon
DB_USER=root
DB_PASS=

UPLOAD_MAX_SIZE=10485760
NEARBY_RADIUS_METERS=100
```

- [ ] **Step 2: Create `.gitignore`**

```
.env
storage/uploads/originals/*
storage/uploads/thumbnails/*
storage/uploads/cards/*
storage/uploads/detail/*
!storage/uploads/*/.gitkeep
vendor/
*.log
.DS_Store
```

- [ ] **Step 3: Create `config/app.php`**

```php
<?php

return [
    'name'   => $_ENV['APP_NAME'] ?? 'Frizon',
    'url'    => $_ENV['APP_URL'] ?? 'http://localhost',
    'debug'  => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',

    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'frizon',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ],

    'upload_max_size'      => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
    'nearby_radius_meters' => (int) ($_ENV['NEARBY_RADIUS_METERS'] ?? 100),
];
```

- [ ] **Step 4: Create `app/bootstrap.php`**

```php
<?php

declare(strict_types=1);

// Load .env
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Load config
$config = require dirname(__DIR__) . '/config/app.php';

// PDO connection
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['name']
);
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Session
session_start();

// Helpers
require __DIR__ . '/Helpers/view.php';
require __DIR__ . '/Helpers/redirect.php';
require __DIR__ . '/Helpers/flash.php';
```

- [ ] **Step 5: Create `public/.htaccess`**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

- [ ] **Step 6: Create `public/index.php`**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/Router.php';
require_once dirname(__DIR__) . '/routes/web.php';

$router = new Router();
registerRoutes($router);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri, $pdo, $config);
```

- [ ] **Step 7: Create helper files**

`app/Helpers/view.php`:
```php
<?php

function view(string $template, array $data = [], string $layout = 'app'): void
{
    extract($data);
    $contentFile = dirname(__DIR__, 2) . '/views/' . $template . '.php';

    ob_start();
    require $contentFile;
    $content = ob_get_clean();

    require dirname(__DIR__, 2) . '/views/layouts/' . $layout . '.php';
}
```

`app/Helpers/redirect.php`:
```php
<?php

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
```

`app/Helpers/flash.php`:
```php
<?php

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
```

- [ ] **Step 8: Create `README.md`**

```markdown
# Frizon.org

Private travel log and trip planner for Mattias & Ulrica.

## Setup

1. Copy `.env.example` to `.env` and fill in database credentials
2. Create MySQL database: `CREATE DATABASE frizon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Run migration: `mysql -u root frizon < database/migrations/001_initial_schema.sql`
4. Optional seed data: `mysql -u root frizon < database/seed.sql`
5. Point LiteSpeed document root to `public/`
6. Create upload directories: `mkdir -p storage/uploads/{originals,thumbnails,cards,detail}`

## Requirements

- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.6+
- GD or Imagick extension (for image resizing)
- LiteSpeed (or Apache with mod_rewrite)
```

- [ ] **Step 9: Create `.gitkeep` files for upload directories and empty directories**

Run:
```bash
mkdir -p storage/uploads/{originals,thumbnails,cards,detail}
touch storage/uploads/originals/.gitkeep
touch storage/uploads/thumbnails/.gitkeep
touch storage/uploads/cards/.gitkeep
touch storage/uploads/detail/.gitkeep
```

- [ ] **Step 10: Commit**

```bash
git add .env.example .gitignore README.md config/app.php app/bootstrap.php app/Helpers/ public/index.php public/.htaccess storage/
git commit -m "feat: project skeleton with bootstrap, config, routing, helpers"
```

---

## Task 2: Router

**Files:**
- Create: `app/Router.php`
- Create: `routes/web.php`

- [ ] **Step 1: Create `app/Router.php`**

```php
<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('GET', $pattern, $controller, $method);
    }

    public function post(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('POST', $pattern, $controller, $method);
    }

    public function put(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('PUT', $pattern, $controller, $method);
    }

    public function delete(string $pattern, string $controller, string $method): void
    {
        $this->addRoute('DELETE', $pattern, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $pattern, string $controller, string $method): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method'     => $httpMethod,
            'pattern'    => '#^' . $regex . '$#',
            'controller' => $controller,
            'action'     => $method,
        ];
    }

    public function dispatch(string $method, string $uri, PDO $pdo, array $config): void
    {
        // Support PUT/DELETE via _method field in POST forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $controllerFile = dirname(__DIR__) . '/app/Controllers/' . $route['controller'] . '.php';
                require_once $controllerFile;

                $controller = new $route['controller']($pdo, $config);
                $controller->{$route['action']}($params);
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 — Sidan hittades inte</h1>';
    }
}
```

- [ ] **Step 2: Create `routes/web.php` with initial routes**

```php
<?php

function registerRoutes(Router $router): void
{
    // Auth
    $router->get('/login', 'AuthController', 'showLogin');
    $router->post('/login', 'AuthController', 'login');
    $router->post('/logout', 'AuthController', 'logout');

    // Dashboard
    $router->get('/', 'DashboardController', 'index');

    // Places
    $router->get('/platser', 'PlaceController', 'index');
    $router->get('/platser/ny', 'PlaceController', 'create');
    $router->post('/platser', 'PlaceController', 'store');
    $router->get('/platser/{slug}', 'PlaceController', 'show');
    $router->get('/platser/{slug}/redigera', 'PlaceController', 'edit');
    $router->put('/platser/{slug}', 'PlaceController', 'update');
    $router->delete('/platser/{slug}', 'PlaceController', 'destroy');

    // Visits
    $router->get('/platser/{slug}/besok/nytt', 'VisitController', 'create');
    $router->post('/platser/{slug}/besok', 'VisitController', 'store');
    $router->get('/besok/{id}', 'VisitController', 'show');
    $router->get('/besok/{id}/redigera', 'VisitController', 'edit');
    $router->put('/besok/{id}', 'VisitController', 'update');
    $router->delete('/besok/{id}', 'VisitController', 'destroy');

    // API endpoints (JSON)
    $router->get('/api/platser/nearby', 'PlaceController', 'nearby');
    $router->post('/api/images/upload', 'VisitController', 'uploadImage');
    $router->get('/api/tags/suitable-for', 'VisitController', 'suitableForSuggestions');
}
```

- [ ] **Step 3: Verify router works**

Create a temporary test route and hit it with curl:
```bash
curl -s http://localhost/login | head -20
```
Expected: Should not 500. If no server yet, verify PHP syntax:
```bash
php -l app/Router.php && php -l routes/web.php
```
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Router.php routes/web.php
git commit -m "feat: simple regex router with Swedish URL paths"
```

---

## Task 3: Database migration

**Files:**
- Create: `database/migrations/001_initial_schema.sql`
- Create: `database/seed.sql`

- [ ] **Step 1: Create `database/migrations/001_initial_schema.sql`**

```sql
-- Frizon.org Phase 1 Schema
-- Tables: users, places, place_tags, visits, visit_images, visit_ratings

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS places (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    lat DECIMAL(10, 7) NOT NULL,
    lng DECIMAL(10, 7) NOT NULL,
    address_text VARCHAR(500) NULL,
    country_code CHAR(2) NULL,
    place_type ENUM(
        'breakfast','lunch','dinner','fika','sight',
        'shopping','stellplatz','wild_camping','camping'
    ) NOT NULL DEFAULT 'stellplatz',
    public_allowed TINYINT(1) NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_toplisted TINYINT(1) NOT NULL DEFAULT 0,
    toplist_order INT UNSIGNED NULL,
    default_public_text TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_places_type (place_type),
    INDEX idx_places_country (country_code),
    INDEX idx_places_public (public_allowed),
    INDEX idx_places_coords (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS place_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id INT UNSIGNED NOT NULL,
    tag VARCHAR(100) NOT NULL,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_place_tag (place_id, tag),
    INDEX idx_tags_tag (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    visited_at DATE NOT NULL,
    raw_note TEXT NULL,
    plus_notes TEXT NULL,
    minus_notes TEXT NULL,
    tips_notes TEXT NULL,
    price_level ENUM('free','low','medium','high') NULL,
    would_return ENUM('yes','maybe','no') NULL,
    suitable_for VARCHAR(500) NULL COMMENT 'Comma-delimited freetext values',
    things_to_note TEXT NULL,
    ai_draft_id INT UNSIGNED NULL,
    approved_public_text TEXT NULL,
    ready_for_publish TINYINT(1) NOT NULL DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_visits_place (place_id),
    INDEX idx_visits_user (user_id),
    INDEX idx_visits_date (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL UNIQUE,
    location_rating TINYINT UNSIGNED NULL CHECK (location_rating BETWEEN 1 AND 5),
    calmness_rating TINYINT UNSIGNED NULL CHECK (calmness_rating BETWEEN 1 AND 5),
    service_rating TINYINT UNSIGNED NULL CHECK (service_rating BETWEEN 1 AND 5),
    value_rating TINYINT UNSIGNED NULL CHECK (value_rating BETWEEN 1 AND 5),
    return_value_rating TINYINT UNSIGNED NULL CHECK (return_value_rating BETWEEN 1 AND 5),
    total_rating_cached DECIMAL(2,1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    image_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    alt_text VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    INDEX idx_images_visit (visit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Create `database/seed.sql`**

```sql
-- Seed users (password: "frizon2026" bcrypt hash)
-- Generate hash with: php -r "echo password_hash('frizon2026', PASSWORD_BCRYPT);"
-- Using a pre-generated hash below. Regenerate for production.
INSERT INTO users (username, email, password_hash, display_name) VALUES
('mattias', 'mattias@frizon.org', '$2y$10$YKEkjGqGMfP5F5H5G5Z5z.5Z5z5Z5z5Z5z5Z5z5Z5z5Z5z5Z5z5Z', 'Mattias'),
('ulrica', 'ulrica@frizon.org', '$2y$10$YKEkjGqGMfP5F5H5G5Z5z.5Z5z5Z5z5Z5z5Z5z5Z5z5Z5z5Z5z5Z', 'Ulrica');

-- NOTE: The bcrypt hashes above are placeholders.
-- After setup, run this to generate real hashes:
-- php -r "echo password_hash('your-password-here', PASSWORD_BCRYPT) . PHP_EOL;"
-- Then update the users table accordingly.

-- Seed some example places
INSERT INTO places (slug, name, lat, lng, country_code, place_type, created_by) VALUES
('hammaro-stellplats', 'Hammarö Ställplats', 59.3299, 13.5227, 'SE', 'stellplatz', 1),
('cafe-sjokanten', 'Cafe Sjökanten', 58.7530, 17.0086, 'SE', 'fika', 1),
('normandie-camping', 'Camping Le Grand Large', 48.8400, -1.5050, 'FR', 'camping', 1);

-- Seed a visit
INSERT INTO visits (place_id, user_id, visited_at, raw_note, price_level, would_return, suitable_for) VALUES
(1, 1, '2025-06-15', 'Lugnt och fint. Nära vattnet. Bra service.', 'low', 'yes', 'husbilar,hundar,familjer');

-- Seed ratings for the visit
INSERT INTO visit_ratings (visit_id, location_rating, calmness_rating, service_rating, value_rating, return_value_rating, total_rating_cached) VALUES
(1, 4, 5, 3, 4, 5, 4.2);
```

- [ ] **Step 3: Verify SQL syntax**

```bash
php -r "
\$sql = file_get_contents('database/migrations/001_initial_schema.sql');
echo 'Migration SQL length: ' . strlen(\$sql) . ' bytes' . PHP_EOL;
echo 'Tables: ' . substr_count(\$sql, 'CREATE TABLE') . PHP_EOL;
"
```
Expected: `Migration SQL length: ~3000+ bytes` and `Tables: 6`

- [ ] **Step 4: Commit**

```bash
git add database/
git commit -m "feat: Phase 1 database schema — users, places, visits, ratings, images"
```

---

## Task 4: CSRF service and Auth service

**Files:**
- Create: `app/Services/CsrfService.php`
- Create: `app/Services/Auth.php`
- Create: `app/Middleware/AuthMiddleware.php`

- [ ] **Step 1: Create `app/Services/CsrfService.php`**

```php
<?php

declare(strict_types=1);

class CsrfService
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify(): bool
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals(self::token(), $token);
    }

    public static function requireValid(): void
    {
        if (!self::verify()) {
            http_response_code(403);
            die('Ogiltig CSRF-token. Ladda om sidan och försök igen.');
        }
    }
}
```

- [ ] **Step 2: Create `app/Services/Auth.php`**

```php
<?php

declare(strict_types=1);

class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function attempt(string $username, string $password): bool
    {
        $stmt = $this->pdo->prepare('SELECT id, password_hash, display_name FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['display_name'];
        session_regenerate_id(true);
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function userName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Du måste logga in.');
            redirect('/login');
        }
    }
}
```

- [ ] **Step 3: Create `app/Middleware/AuthMiddleware.php`**

```php
<?php

declare(strict_types=1);

class AuthMiddleware
{
    public static function handle(): void
    {
        Auth::requireLogin();
    }
}
```

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l app/Services/CsrfService.php && php -l app/Services/Auth.php && php -l app/Middleware/AuthMiddleware.php
```
Expected: `No syntax errors detected` for all three

- [ ] **Step 5: Commit**

```bash
git add app/Services/CsrfService.php app/Services/Auth.php app/Middleware/AuthMiddleware.php
git commit -m "feat: CSRF protection, session auth, auth middleware"
```

---

## Task 5: Auth controller and login view

**Files:**
- Create: `app/Controllers/AuthController.php`
- Create: `views/layouts/auth.php`
- Create: `views/auth/login.php`
- Create: `views/partials/csrf-field.php`

- [ ] **Step 1: Create `views/partials/csrf-field.php`**

```php
<?php require_once dirname(__DIR__, 2) . '/app/Services/CsrfService.php'; ?>
<?= CsrfService::field() ?>
```

- [ ] **Step 2: Create `views/layouts/auth.php`**

```php
<?php
$pageTitle = $pageTitle ?? 'Logga in';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Frizon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <?= $content ?>
    </div>
</body>
</html>
```

- [ ] **Step 3: Create `views/auth/login.php`**

```php
<div class="auth-card">
    <div class="auth-logo">
        <img src="/img/frizon-logo.png" alt="Frizon of Sweden" class="auth-logo__image">
        <span class="auth-tagline">of Sweden</span>
    </div>

    <?php if ($error = flash('error')): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login" class="auth-form">
        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

        <div class="form-group">
            <label for="username" class="form-label">Användarnamn</label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-input"
                required
                autofocus
                autocomplete="username"
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Lösenord</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-input"
                required
                autocomplete="current-password"
            >
        </div>

        <button type="submit" class="btn btn-primary btn--full">Logga in</button>
    </form>
</div>
```

- [ ] **Step 4: Create `app/Controllers/AuthController.php`**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';

class AuthController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function showLogin(array $params): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        view('auth/login', [], 'auth');
    }

    public function login(array $params): void
    {
        CsrfService::requireValid();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            flash('error', 'Fyll i alla fält.');
            redirect('/login');
        }

        $auth = new Auth($this->pdo);
        if ($auth->attempt($username, $password)) {
            redirect('/');
        }

        flash('error', 'Fel användarnamn eller lösenord.');
        redirect('/login');
    }

    public function logout(array $params): void
    {
        CsrfService::requireValid();
        Auth::logout();
        redirect('/login');
    }
}
```

- [ ] **Step 5: Verify PHP syntax**

```bash
php -l app/Controllers/AuthController.php && php -l views/layouts/auth.php && php -l views/auth/login.php
```
Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/AuthController.php views/layouts/auth.php views/auth/login.php views/partials/csrf-field.php
git commit -m "feat: login page with auth controller, CSRF, Swedish labels"
```

---

## Task 6: CSS design system foundation

**Files:**
- Create: `public/css/variables.css`
- Create: `public/css/reset.css`
- Create: `public/css/base.css`
- Create: `public/css/layout.css`
- Create: `public/css/components/buttons.css`
- Create: `public/css/components/forms.css`
- Create: `public/css/components/navigation.css`
- Create: `public/css/components/cards.css`
- Create: `public/css/components/toast.css`
- Create: `public/css/components/ratings.css`
- Create: `public/css/components/tags.css`
- Create: `public/css/components/modals.css`
- Create: `public/css/components/map.css`
- Create: `public/css/components/skeleton.css`
- Create: `public/css/components/gallery.css`
- Create: `public/css/pages/auth.css`
- Create: `public/css/pages/dashboard.css`
- Create: `public/css/pages/places.css`
- Create: `public/css/utilities.css`
- Create: `public/css/main.css`

This is a large task. The CSS should implement the design system from `docs/UI-DESIGN.md` section 7.1 (variables), sections 1-2 (graphic profile and components), and section 5 (responsive strategy).

- [ ] **Step 1: Create `public/css/variables.css`**

Copy the full `:root` block from UI-DESIGN.md section 7.1 verbatim. It contains all custom properties: brand colors, accent colors, semantics, stop-type colors, typography, spacing, radii, shadows, transitions, and layout variables.

```css
:root {
  /* Varumärkesfärger */
  --color-brand-primary:    #5D7E9A;
  --color-brand-dark:       #3D4F5F;
  --color-brand-mid:        #4A6070;
  --color-brand-light:      #8FA4B8;
  --color-brand-muted:      #BDD0DF;
  --color-brand-off-white:  #F5F7F9;

  /* Accentfärger */
  --color-accent:           #2C5F6A;
  --color-accent-mid:       #3D7A87;
  --color-accent-light:     #6BAAB7;

  /* Sand/varm accent */
  --color-warm:             #E8DFC8;
  --color-warm-dark:        #C4B89A;

  /* Semantiska */
  --color-success:          #4A8C6F;
  --color-success-bg:       #EAF5EF;
  --color-warning:          #C8862A;
  --color-warning-bg:       #FDF3E3;
  --color-error:            #B54040;
  --color-error-bg:         #FDEAEA;
  --color-info:             #5D7E9A;
  --color-info-bg:          #EAF0F5;

  /* Neutraler */
  --color-white:            #FFFFFF;
  --color-bg:               #F5F7F9;
  --color-surface:          #FFFFFF;
  --color-border:           #BDD0DF;
  --color-text:             #3D4F5F;
  --color-text-muted:       #4A6070;
  --color-text-subtle:      #8FA4B8;

  /* Stopptyper */
  --color-stop-breakfast:   #E8A44A;
  --color-stop-lunch:       #6BAE7A;
  --color-stop-dinner:      #7A5F9E;
  --color-stop-fika:        #C47B4A;
  --color-stop-sight:       #4A8CC4;
  --color-stop-shopping:    #C44A7A;
  --color-stop-stellplatz:  #5D9E7A;
  --color-stop-wildcamp:    #4A7A5D;
  --color-stop-camping:     #7AAE5D;

  /* Typografi */
  --font-base:    'DM Sans', 'Inter', system-ui, sans-serif;
  --font-script:  'Dancing Script', cursive;

  /* Textstorlekar */
  --text-xs:    0.75rem;
  --text-sm:    0.875rem;
  --text-base:  1rem;
  --text-lg:    1.125rem;
  --text-xl:    1.25rem;
  --text-2xl:   1.5rem;
  --text-3xl:   2rem;

  /* Vikter */
  --weight-regular:  400;
  --weight-medium:   500;
  --weight-semibold: 600;
  --weight-bold:     700;

  /* Radavstånd */
  --leading-tight:   1.25;
  --leading-normal:  1.5;
  --leading-relaxed: 1.6;

  /* Avstånd */
  --space-1:   0.25rem;
  --space-2:   0.5rem;
  --space-3:   0.75rem;
  --space-4:   1rem;
  --space-5:   1.25rem;
  --space-6:   1.5rem;
  --space-8:   2rem;
  --space-10:  2.5rem;
  --space-12:  3rem;
  --space-16:  4rem;
  --space-20:  5rem;

  /* Kantradie */
  --radius-sm:    0.25rem;
  --radius-md:    0.5rem;
  --radius-lg:    0.75rem;
  --radius-xl:    1rem;
  --radius-2xl:   1.5rem;
  --radius-full:  9999px;

  /* Skuggor */
  --shadow-sm:    0 1px 3px rgba(61, 79, 95, 0.10), 0 1px 2px rgba(61, 79, 95, 0.06);
  --shadow-md:    0 4px 6px rgba(61, 79, 95, 0.10), 0 2px 4px rgba(61, 79, 95, 0.06);
  --shadow-lg:    0 10px 15px rgba(61, 79, 95, 0.12), 0 4px 6px rgba(61, 79, 95, 0.05);
  --shadow-xl:    0 20px 25px rgba(61, 79, 95, 0.15), 0 10px 10px rgba(61, 79, 95, 0.04);
  --shadow-card:  0 2px 8px rgba(61, 79, 95, 0.10);
  --shadow-float: 0 8px 24px rgba(61, 79, 95, 0.20);

  /* Övergångar */
  --transition-fast:   150ms ease-out;
  --transition-normal: 200ms ease-out;
  --transition-slow:   300ms ease-out;
  --transition-spring: 250ms cubic-bezier(0.34, 1.56, 0.64, 1);

  /* Layout */
  --sidebar-width:     240px;
  --header-height-mob: 56px;
  --header-height-desk: 64px;
  --nav-height-mob:    64px;
  --content-max-width: 1200px;
  --form-max-width:    600px;
}
```

- [ ] **Step 2: Create `public/css/reset.css`**

```css
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html {
  -webkit-text-size-adjust: 100%;
  -moz-text-size-adjust: 100%;
}

body {
  min-height: 100dvh;
}

img, picture, video, canvas, svg {
  display: block;
  max-width: 100%;
}

input, button, textarea, select {
  font: inherit;
  color: inherit;
}

a {
  color: inherit;
  text-decoration: none;
}

ul, ol {
  list-style: none;
}

button {
  cursor: pointer;
  border: none;
  background: none;
}
```

- [ ] **Step 3: Create `public/css/base.css`**

```css
body {
  font-family: var(--font-base);
  font-size: var(--text-base);
  font-weight: var(--weight-regular);
  line-height: var(--leading-normal);
  color: var(--color-text);
  background-color: var(--color-bg);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

h1, h2, h3, h4 {
  font-weight: var(--weight-bold);
  line-height: var(--leading-tight);
  color: var(--color-brand-dark);
}

h1 { font-size: var(--text-3xl); }
h2 { font-size: var(--text-2xl); }
h3 { font-size: var(--text-xl); }
h4 { font-size: var(--text-lg); }

p { margin-bottom: var(--space-4); }
p:last-child { margin-bottom: 0; }

a:hover { color: var(--color-accent); }

.text-script {
  font-family: var(--font-script);
}

.alert {
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  margin-bottom: var(--space-4);
}

.alert--error {
  background: var(--color-error-bg);
  color: var(--color-error);
  border-left: 4px solid var(--color-error);
}

.alert--success {
  background: var(--color-success-bg);
  color: var(--color-success);
  border-left: 4px solid var(--color-success);
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

- [ ] **Step 4: Create `public/css/layout.css`**

Implement the private-side layout: mobile bottom navbar space, desktop sidebar. Reference UI-DESIGN.md sections 2.1 and 5.2.

```css
/* Mobile-first layout */
.app-layout {
  min-height: 100dvh;
  padding-bottom: calc(var(--nav-height-mob) + env(safe-area-inset-bottom, 16px));
}

.app-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: var(--header-height-mob);
  background: var(--color-white);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  padding: 0 var(--space-4);
  gap: var(--space-3);
}

.app-header__logo {
  font-weight: var(--weight-bold);
  font-size: var(--text-lg);
  color: var(--color-brand-dark);
}

.app-header__title {
  flex: 1;
  font-size: var(--text-lg);
  font-weight: var(--weight-semibold);
}

.app-header__actions {
  display: flex;
  gap: var(--space-2);
}

.app-main {
  padding: var(--space-4);
  max-width: var(--content-max-width);
}

/* Desktop: sidebar */
@media (min-width: 1024px) {
  .app-layout {
    padding-bottom: 0;
    display: grid;
    grid-template-columns: var(--sidebar-width) 1fr;
    grid-template-rows: auto 1fr;
  }

  .app-sidebar {
    grid-row: 1 / -1;
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--color-brand-dark);
    color: var(--color-white);
    padding: var(--space-4);
    overflow-y: auto;
    z-index: 200;
  }

  .app-header {
    height: var(--header-height-desk);
  }

  .app-main {
    padding: var(--space-6);
    margin: 0 auto;
  }

  .bottom-nav {
    display: none;
  }

  .app-sidebar--mobile-only {
    display: none;
  }
}

@media (max-width: 1023px) {
  .app-sidebar {
    display: none;
  }
}
```

- [ ] **Step 5: Create `public/css/components/buttons.css`**

Implement all button styles from UI-DESIGN.md section 2.3.

```css
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  font-size: 15px;
  font-weight: var(--weight-semibold);
  border-radius: var(--radius-md);
  padding: 12px 24px;
  min-height: 48px;
  border: none;
  cursor: pointer;
  transition: background var(--transition-fast), transform var(--transition-fast);
  text-decoration: none;
}

.btn-primary {
  background: var(--color-accent);
  color: var(--color-white);
}

.btn-primary:hover { background: var(--color-accent-mid); }
.btn-primary:active { background: #1E4A53; transform: translateY(1px); }
.btn-primary:disabled { background: var(--color-brand-muted); color: var(--color-brand-light); cursor: not-allowed; }

.btn-secondary {
  background: transparent;
  color: var(--color-accent);
  border: 2px solid var(--color-accent);
  padding: 10px 24px;
}

.btn-secondary:hover { background: rgba(44, 95, 106, 0.08); }

.btn-ghost {
  background: transparent;
  color: var(--color-text-muted);
  padding: 10px 16px;
  min-height: 44px;
}

.btn-ghost:hover { background: rgba(61, 79, 95, 0.08); }

.btn-danger {
  background: var(--color-error);
  color: var(--color-white);
}

.btn-danger:hover { background: #9A3030; }

.btn--full { width: 100%; }
.btn--sm { padding: 8px 16px; font-size: 13px; min-height: 36px; }
.btn--lg { padding: 14px 28px; font-size: 16px; min-height: 52px; }

.fab {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--color-accent);
  color: var(--color-white);
  border: none;
  box-shadow: var(--shadow-float);
  display: flex;
  align-items: center;
  justify-content: center;
}

.fab:hover { background: var(--color-accent-mid); }
```

- [ ] **Step 6: Create `public/css/components/forms.css`**

From UI-DESIGN.md section 2.4.

```css
.form-group {
  margin-bottom: var(--space-4);
}

.form-label {
  display: block;
  font-size: var(--text-sm);
  font-weight: var(--weight-medium);
  color: var(--color-text-muted);
  margin-bottom: 6px;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  min-height: 48px;
  padding: 12px 16px;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-white);
  font-size: 16px; /* Prevents iOS zoom */
  color: var(--color-text);
  transition: border-color var(--transition-fast);
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  border-color: var(--color-accent-mid);
  outline: none;
  box-shadow: 0 0 0 3px rgba(61, 122, 135, 0.15);
}

.form-input--error,
.form-textarea--error {
  border-color: var(--color-error);
  box-shadow: 0 0 0 3px rgba(181, 64, 64, 0.10);
}

.form-textarea {
  min-height: 120px;
  resize: vertical;
  line-height: var(--leading-relaxed);
}

.form-textarea--note {
  background: #FDFCF8;
  border-color: var(--color-warm-dark);
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M4 6L8 10L12 6' stroke='%234A6070' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 40px;
  cursor: pointer;
}

.form-error {
  font-size: var(--text-xs);
  color: var(--color-error);
  margin-top: 4px;
}

.form-hint {
  font-size: var(--text-xs);
  color: var(--color-text-subtle);
  margin-top: 4px;
}
```

- [ ] **Step 7: Create `public/css/components/navigation.css`**

Mobile bottom nav from UI-DESIGN.md section 2.1.

```css
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: calc(var(--nav-height-mob) + env(safe-area-inset-bottom, 0px));
  padding-bottom: env(safe-area-inset-bottom, 0px);
  background: var(--color-white);
  border-top: 1px solid var(--color-border);
  box-shadow: 0 -4px 12px rgba(61, 79, 95, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-around;
  z-index: 150;
}

.bottom-nav__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: 8px 12px;
  color: var(--color-brand-mid);
  opacity: 0.65;
  font-size: var(--text-xs);
  transition: opacity var(--transition-fast);
  text-decoration: none;
  min-width: 48px;
  min-height: 48px;
  justify-content: center;
}

.bottom-nav__item--active {
  color: var(--color-accent);
  opacity: 1;
}

.bottom-nav__item svg {
  width: 24px;
  height: 24px;
}

.bottom-nav__fab {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--color-accent);
  color: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: -12px;
  box-shadow: var(--shadow-float);
  border: none;
  cursor: pointer;
}

.bottom-nav__fab svg {
  width: 28px;
  height: 28px;
}

/* Desktop sidebar nav */
.sidebar-nav {
  padding: var(--space-4) 0;
}

.sidebar-nav__label {
  font-size: 11px;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  padding: var(--space-4) var(--space-4) var(--space-2);
  letter-spacing: 0.05em;
}

.sidebar-nav__item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: 0 var(--space-4);
  height: 48px;
  color: rgba(255, 255, 255, 0.65);
  font-size: var(--text-sm);
  transition: background var(--transition-fast);
  text-decoration: none;
}

.sidebar-nav__item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: var(--color-white);
}

.sidebar-nav__item--active {
  background: rgba(255, 255, 255, 0.15);
  border-left: 3px solid var(--color-accent-light);
  color: var(--color-white);
}

.sidebar-nav__item svg {
  width: 20px;
  height: 20px;
}
```

- [ ] **Step 8: Create remaining component CSS files**

Create these files implementing the respective UI-DESIGN.md sections:

`public/css/components/cards.css` — section 2.2 (place, trip, visit cards)
`public/css/components/ratings.css` — section 2.8 (star display, sub-rating dots)
`public/css/components/tags.css` — section 2.4 tag/autocomplete styles
`public/css/components/modals.css` — section 2.6 (bottom sheets, desktop modals)
`public/css/components/toast.css` — section 2.7 (toast notifications)
`public/css/components/map.css` — section 6.2-6.3 (markers, popups, clusters)
`public/css/components/skeleton.css` — section 2.9 (shimmer loading)
`public/css/components/gallery.css` — section 2.4 photo upload zone

Each file should implement the exact styles from the design doc. The file content is defined in UI-DESIGN.md — translate the specifications to CSS.

- [ ] **Step 9: Create page-specific CSS**

`public/css/pages/auth.css` — login page styling from section 3.1:

```css
.auth-page {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(180deg, var(--color-brand-primary) 0%, var(--color-brand-light) 100%);
  padding: var(--space-4);
}

.auth-container {
  width: 100%;
  max-width: 400px;
}

.auth-card {
  background: var(--color-white);
  padding: var(--space-8);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
}

.auth-logo {
  text-align: center;
  margin-bottom: var(--space-6);
}

.auth-logo__image {
  width: 120px;
  height: auto;
  margin: 0 auto var(--space-3);
}

.auth-tagline {
  font-family: var(--font-script);
  font-size: 22px;
  color: var(--color-brand-primary);
}

.auth-form {
  margin-top: var(--space-6);
}
```

`public/css/pages/dashboard.css` and `public/css/pages/places.css` — create minimal starter styles (will be expanded as views are built):

```css
/* dashboard.css */
.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-3);
  margin-bottom: var(--space-6);
}

.stat-card {
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  text-align: center;
}

.stat-card__number {
  font-size: var(--text-3xl);
  font-weight: var(--weight-bold);
  color: var(--color-brand-dark);
}

.stat-card__label {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
```

- [ ] **Step 10: Create `public/css/utilities.css`**

```css
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.text-center { text-align: center; }
.text-muted { color: var(--color-text-muted); }
.text-sm { font-size: var(--text-sm); }
.text-xs { font-size: var(--text-xs); }

.mt-2 { margin-top: var(--space-2); }
.mt-4 { margin-top: var(--space-4); }
.mt-6 { margin-top: var(--space-6); }
.mb-2 { margin-bottom: var(--space-2); }
.mb-4 { margin-bottom: var(--space-4); }
.mb-6 { margin-bottom: var(--space-6); }

.flex { display: flex; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.flex-center { display: flex; align-items: center; justify-content: center; }
.gap-2 { gap: var(--space-2); }
.gap-3 { gap: var(--space-3); }
.gap-4 { gap: var(--space-4); }
```

- [ ] **Step 11: Create `public/css/main.css`**

```css
/* Frizon.org — Main CSS
   Import order matters: variables → reset → base → layout → components → pages → utilities */

@import 'variables.css';
@import 'reset.css';
@import 'base.css';
@import 'layout.css';

/* Components */
@import 'components/buttons.css';
@import 'components/forms.css';
@import 'components/navigation.css';
@import 'components/cards.css';
@import 'components/ratings.css';
@import 'components/tags.css';
@import 'components/modals.css';
@import 'components/toast.css';
@import 'components/map.css';
@import 'components/skeleton.css';
@import 'components/gallery.css';

/* Pages */
@import 'pages/auth.css';
@import 'pages/dashboard.css';
@import 'pages/places.css';

/* Utilities (last — highest specificity intent) */
@import 'utilities.css';
```

- [ ] **Step 12: Copy logo to public/img**

```bash
cp frizon.logo.png public/img/frizon-logo.png
```

- [ ] **Step 13: Commit**

```bash
git add public/css/ public/img/frizon-logo.png
git commit -m "feat: complete CSS design system from Frizon graphic profile"
```

---

## Task 7: App layout and navigation views

**Files:**
- Create: `views/layouts/app.php`
- Create: `views/partials/header.php`
- Create: `views/partials/nav-mobile.php`
- Create: `views/partials/nav-desktop.php`
- Create: `views/partials/toast.php`

- [ ] **Step 1: Create `views/partials/header.php`**

```php
<header class="app-header">
    <span class="app-header__logo">Frizon</span>
    <span class="app-header__title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
    <div class="app-header__actions">
        <button class="fab fab--header" id="gps-quick-add" aria-label="Spara plats här">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/><line x1="12" y1="7" x2="12" y2="3"/></svg>
        </button>
    </div>
</header>
```

- [ ] **Step 2: Create `views/partials/nav-mobile.php`**

```php
<?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<nav class="bottom-nav" aria-label="Huvudnavigation">
    <a href="/" class="bottom-nav__item <?= $currentPath === '/' ? 'bottom-nav__item--active' : '' ?>" aria-label="Karta">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
        <span>Karta</span>
    </a>
    <a href="/platser" class="bottom-nav__item <?= str_starts_with($currentPath, '/platser') ? 'bottom-nav__item--active' : '' ?>" aria-label="Platser">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>Platser</span>
    </a>
    <button class="bottom-nav__fab" id="nav-gps-add" aria-label="Spara plats här">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
    <a href="/resor" class="bottom-nav__item <?= str_starts_with($currentPath, '/resor') ? 'bottom-nav__item--active' : '' ?>" aria-label="Resor">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        <span>Resor</span>
    </a>
    <a href="/mer" class="bottom-nav__item" aria-label="Mer">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        <span>Mer</span>
    </a>
</nav>
```

- [ ] **Step 3: Create `views/partials/nav-desktop.php`**

```php
<?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<aside class="app-sidebar">
    <div class="sidebar-logo">
        <img src="/img/frizon-logo.png" alt="Frizon" width="48" style="border-radius:50%;">
        <span style="font-weight:700; font-size:1.1rem;">Frizon</span>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav__label">Privat</div>
        <a href="/" class="sidebar-nav__item <?= $currentPath === '/' ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/></svg>
            Karta
        </a>
        <a href="/platser" class="sidebar-nav__item <?= str_starts_with($currentPath, '/platser') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            Platser
        </a>
        <a href="/resor" class="sidebar-nav__item <?= str_starts_with($currentPath, '/resor') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
            Resor
        </a>
    </nav>
    <div class="sidebar-footer" style="margin-top:auto; padding-top:var(--space-4); border-top:1px solid rgba(255,255,255,0.1);">
        <span style="font-size:var(--text-sm); opacity:0.65;"><?= htmlspecialchars(Auth::userName() ?? '') ?></span>
        <form method="POST" action="/logout" style="display:inline;">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <button type="submit" class="btn-ghost" style="color:rgba(255,255,255,0.5); font-size:var(--text-sm);">Logga ut</button>
        </form>
    </div>
</aside>
```

- [ ] **Step 4: Create `views/partials/toast.php`**

```php
<?php
$successMsg = flash('success');
$errorMsg = flash('error');
$infoMsg = flash('info');
?>
<?php if ($successMsg): ?>
    <div class="toast toast--success" role="alert"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="toast toast--error" role="alert"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($infoMsg): ?>
    <div class="toast toast--info" role="alert"><?= htmlspecialchars($infoMsg) ?></div>
<?php endif; ?>
```

- [ ] **Step 5: Create `views/layouts/app.php`**

```php
<?php
require_once dirname(__DIR__, 2) . '/app/Services/Auth.php';
Auth::requireLogin();
$pageTitle = $pageTitle ?? 'Frizon';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Frizon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="/css/main.css">
</head>
<body class="app-layout">
    <?php include dirname(__DIR__) . '/partials/nav-desktop.php'; ?>
    <?php include dirname(__DIR__) . '/partials/header.php'; ?>

    <main class="app-main">
        <?php include dirname(__DIR__) . '/partials/toast.php'; ?>
        <?= $content ?>
    </main>

    <?php include dirname(__DIR__) . '/partials/nav-mobile.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
```

- [ ] **Step 6: Verify PHP syntax**

```bash
php -l views/layouts/app.php && php -l views/partials/header.php && php -l views/partials/nav-mobile.php && php -l views/partials/nav-desktop.php && php -l views/partials/toast.php
```
Expected: `No syntax errors detected` for all

- [ ] **Step 7: Commit**

```bash
git add views/layouts/app.php views/partials/
git commit -m "feat: app layout with mobile bottom nav, desktop sidebar, toast notifications"
```

---

## Task 8: Place model and controller

**Files:**
- Create: `app/Models/Place.php`
- Create: `app/Controllers/PlaceController.php`

- [ ] **Step 1: Create `app/Models/Place.php`**

```php
<?php

declare(strict_types=1);

class Place
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT p.*, COUNT(v.id) as visit_count,
                AVG(vr.total_rating_cached) as avg_rating
                FROM places p
                LEFT JOIN visits v ON v.place_id = p.id
                LEFT JOIN visit_ratings vr ON vr.visit_id = v.id';
        $where = [];
        $params = [];

        if (!empty($filters['place_type'])) {
            $where[] = 'p.place_type = ?';
            $params[] = $filters['place_type'];
        }

        if (!empty($filters['country_code'])) {
            $where[] = 'p.country_code = ?';
            $params[] = $filters['country_code'];
        }

        if (!empty($filters['search'])) {
            $where[] = 'p.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY p.id ORDER BY p.updated_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM places WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM places WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO places (slug, name, lat, lng, address_text, country_code, place_type, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['slug'],
            $data['name'],
            $data['lat'],
            $data['lng'],
            $data['address_text'] ?? null,
            $data['country_code'] ?? null,
            $data['place_type'],
            $data['created_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE places SET name = ?, lat = ?, lng = ?, address_text = ?,
            country_code = ?, place_type = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['name'],
            $data['lat'],
            $data['lng'],
            $data['address_text'] ?? null,
            $data['country_code'] ?? null,
            $data['place_type'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM places WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findNearby(float $lat, float $lng, int $radiusMeters): array
    {
        // Haversine formula for distance in meters
        $sql = '
            SELECT *, (
                6371000 * acos(
                    cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?))
                    + sin(radians(?)) * sin(radians(lat))
                )
            ) AS distance_meters
            FROM places
            HAVING distance_meters <= ?
            ORDER BY distance_meters ASC
            LIMIT 5
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$lat, $lng, $lat, $radiusMeters]);
        return $stmt->fetchAll();
    }

    public static function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name);
        // Swedish character transliteration
        $slug = strtr($slug, [
            'å' => 'a', 'ä' => 'a', 'ö' => 'o',
            'é' => 'e', 'è' => 'e', 'ü' => 'u',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}
```

- [ ] **Step 2: Create `app/Controllers/PlaceController.php`**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/Place.php';

class PlaceController
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
        $place = new Place($this->pdo);

        $filters = [
            'place_type'   => $_GET['type'] ?? null,
            'country_code' => $_GET['country'] ?? null,
            'search'       => $_GET['q'] ?? null,
        ];

        $places = $place->all($filters);
        $pageTitle = 'Platser';
        view('places/index', compact('places', 'pageTitle', 'filters'));
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);

        if (!$p) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
        }

        // Get visits for this place
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ?
            ORDER BY v.visited_at DESC
        ');
        $stmt->execute([$p['id']]);
        $visits = $stmt->fetchAll();

        // Get tags
        $tagStmt = $this->pdo->prepare('SELECT tag FROM place_tags WHERE place_id = ?');
        $tagStmt->execute([$p['id']]);
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = $p['name'];
        view('places/show', compact('p', 'visits', 'tags', 'pageTitle'));
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $pageTitle = 'Ny plats';
        view('places/create', compact('pageTitle'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $name = trim($_POST['name'] ?? '');
        $lat = (float) ($_POST['lat'] ?? 0);
        $lng = (float) ($_POST['lng'] ?? 0);

        if ($name === '' || $lat === 0.0 || $lng === 0.0) {
            flash('error', 'Namn och koordinater krävs.');
            redirect('/platser/ny');
        }

        $place = new Place($this->pdo);
        $place->create([
            'slug'         => Place::generateSlug($name),
            'name'         => $name,
            'lat'          => $lat,
            'lng'          => $lng,
            'address_text' => trim($_POST['address_text'] ?? '') ?: null,
            'country_code' => trim($_POST['country_code'] ?? '') ?: null,
            'place_type'   => $_POST['place_type'] ?? 'stellplatz',
            'created_by'   => Auth::userId(),
        ]);

        flash('success', 'Platsen har sparats!');
        redirect('/platser');
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);

        if (!$p) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
        }

        $pageTitle = 'Redigera ' . $p['name'];
        view('places/edit', compact('p', 'pageTitle'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);

        if (!$p) {
            http_response_code(404);
            return;
        }

        $place->update((int) $p['id'], [
            'name'         => trim($_POST['name'] ?? $p['name']),
            'lat'          => (float) ($_POST['lat'] ?? $p['lat']),
            'lng'          => (float) ($_POST['lng'] ?? $p['lng']),
            'address_text' => trim($_POST['address_text'] ?? '') ?: null,
            'country_code' => trim($_POST['country_code'] ?? '') ?: null,
            'place_type'   => $_POST['place_type'] ?? $p['place_type'],
        ]);

        flash('success', 'Platsen har uppdaterats.');
        redirect('/platser/' . $params['slug']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);

        if ($p) {
            $place->delete((int) $p['id']);
            flash('success', 'Platsen har tagits bort.');
        }

        redirect('/platser');
    }

    public function nearby(array $params): void
    {
        Auth::requireLogin();

        $lat = (float) ($_GET['lat'] ?? 0);
        $lng = (float) ($_GET['lng'] ?? 0);

        if ($lat === 0.0 || $lng === 0.0) {
            header('Content-Type: application/json');
            echo json_encode(['places' => []]);
            return;
        }

        $place = new Place($this->pdo);
        $nearby = $place->findNearby($lat, $lng, $this->config['nearby_radius_meters']);

        header('Content-Type: application/json');
        echo json_encode(['places' => $nearby]);
    }
}
```

- [ ] **Step 3: Verify PHP syntax**

```bash
php -l app/Models/Place.php && php -l app/Controllers/PlaceController.php
```
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Models/Place.php app/Controllers/PlaceController.php
git commit -m "feat: Place model with CRUD, nearby search, slug generation"
```

---

## Task 9: Place views

**Files:**
- Create: `views/places/index.php`
- Create: `views/places/show.php`
- Create: `views/places/create.php`
- Create: `views/places/edit.php`
- Create: `views/partials/place-card.php`

- [ ] **Step 1: Create `views/partials/place-card.php`**

Compact list card from UI-DESIGN.md section 2.2.

```php
<?php
$placeTypes = [
    'breakfast' => 'Frukost', 'lunch' => 'Lunch', 'dinner' => 'Middag',
    'fika' => 'Fika', 'sight' => 'Sevärdhet', 'shopping' => 'Shopping',
    'stellplatz' => 'Ställplats', 'wild_camping' => 'Vildcamping', 'camping' => 'Camping',
];
$typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];
$rating = $place['avg_rating'] ?? null;
$visitCount = $place['visit_count'] ?? 0;
?>
<a href="/platser/<?= htmlspecialchars($place['slug']) ?>" class="place-card">
    <div class="place-card__icon place-card__icon--<?= htmlspecialchars($place['place_type']) ?>">
        <span class="place-card__type-badge"><?= htmlspecialchars($typeLabel) ?></span>
    </div>
    <div class="place-card__body">
        <div class="place-card__name"><?= htmlspecialchars($place['name']) ?></div>
        <div class="place-card__meta">
            <?php if ($place['country_code']): ?>
                <?= htmlspecialchars($place['country_code']) ?>
            <?php endif; ?>
            <?php if ($visitCount > 0): ?>
                · <?= $visitCount ?> besök
            <?php endif; ?>
        </div>
    </div>
    <?php if ($rating): ?>
        <div class="place-card__rating">&#9733; <?= number_format((float) $rating, 1) ?></div>
    <?php endif; ?>
</a>
```

- [ ] **Step 2: Create `views/places/index.php`**

```php
<div class="page-header flex-between mb-4">
    <h2>Platser</h2>
    <a href="/platser/ny" class="btn btn-primary btn--sm">+ Ny plats</a>
</div>

<div class="filter-bar mb-4">
    <form method="GET" action="/platser" class="flex gap-2" style="flex-wrap:wrap;">
        <input type="text" name="q" class="form-input" placeholder="Sök platser..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="max-width:200px; min-height:40px;">
        <select name="type" class="form-select" style="max-width:160px; min-height:40px;" onchange="this.form.submit()">
            <option value="">Alla typer</option>
            <?php
            $types = ['breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika','sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats','wild_camping'=>'Vildcamping','camping'=>'Camping'];
            foreach ($types as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($filters['place_type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost btn--sm">Filtrera</button>
    </form>
</div>

<?php if (empty($places)): ?>
    <div class="empty-state text-center" style="padding:var(--space-12) 0;">
        <p class="text-muted">Inga platser ännu.</p>
        <a href="/platser/ny" class="btn btn-primary mt-4">Lägg till din första plats</a>
    </div>
<?php else: ?>
    <div class="place-list">
        <?php foreach ($places as $place): ?>
            <?php include dirname(__DIR__) . '/partials/place-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

- [ ] **Step 3: Create `views/places/create.php`**

GPS quick-add form from UI-DESIGN.md section 3.5.

```php
<div class="page-header mb-4">
    <a href="/platser" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Ny plats</h2>
</div>

<div id="gps-map" style="width:100%; height:180px; border-radius:var(--radius-lg); margin-bottom:var(--space-4); background:var(--color-brand-muted);"></div>

<form method="POST" action="/platser" class="place-form" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <input type="hidden" name="lat" id="place-lat" value="">
    <input type="hidden" name="lng" id="place-lng" value="">

    <div class="form-group">
        <label for="name" class="form-label">Namn *</label>
        <input type="text" id="name" name="name" class="form-input" required placeholder="Platsnamn...">
    </div>

    <div class="form-group">
        <label class="form-label">Typ</label>
        <div class="chip-row" style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
            <?php
            $types = [
                'stellplatz'=>'Ställplats','camping'=>'Camping','wild_camping'=>'Vildcamping',
                'fika'=>'Fika','lunch'=>'Lunch','dinner'=>'Middag','breakfast'=>'Frukost',
                'sight'=>'Sevärdhet','shopping'=>'Shopping'
            ];
            foreach ($types as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="place_type" value="<?= $val ?>" <?= $val === 'stellplatz' ? 'checked' : '' ?>>
                    <span class="chip chip--<?= $val ?>"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="address_text" class="form-label">Adress (valfritt)</label>
        <input type="text" id="address_text" name="address_text" class="form-input" placeholder="Gatuadress, stad...">
    </div>

    <div class="form-group">
        <label for="country_code" class="form-label">Land</label>
        <select id="country_code" name="country_code" class="form-select">
            <option value="">Välj land</option>
            <option value="SE" selected>Sverige</option>
            <option value="NO">Norge</option>
            <option value="DK">Danmark</option>
            <option value="FI">Finland</option>
            <option value="DE">Tyskland</option>
            <option value="FR">Frankrike</option>
            <option value="IT">Italien</option>
            <option value="ES">Spanien</option>
            <option value="PT">Portugal</option>
            <option value="NL">Nederländerna</option>
            <option value="BE">Belgien</option>
            <option value="AT">Österrike</option>
            <option value="CH">Schweiz</option>
            <option value="PL">Polen</option>
            <option value="CZ">Tjeckien</option>
            <option value="HR">Kroatien</option>
            <option value="GR">Grekland</option>
        </select>
    </div>

    <div class="form-group">
        <label for="raw_note" class="form-label">Kort anteckning (valfritt)</label>
        <textarea id="raw_note" name="raw_note" class="form-textarea form-textarea--note" rows="3" placeholder="Valfri anteckning..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn--full">Spara plats</button>
</form>

<script src="/js/gps.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initGpsCapture('gps-map', 'place-lat', 'place-lng');
});
</script>
```

- [ ] **Step 4: Create `views/places/show.php`**

```php
<div class="page-header mb-4">
    <a href="/platser" class="btn-ghost btn--sm">&larr; Platser</a>
</div>

<div class="place-detail">
    <h1><?= htmlspecialchars($p['name']) ?></h1>

    <div class="place-detail__meta flex gap-3 mb-4 text-sm text-muted">
        <span class="place-card__type-badge place-card__type-badge--<?= htmlspecialchars($p['place_type']) ?>">
            <?php
            $types = ['breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika','sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats','wild_camping'=>'Vildcamping','camping'=>'Camping'];
            echo $types[$p['place_type']] ?? $p['place_type'];
            ?>
        </span>
        <?php if ($p['country_code']): ?>
            <span><?= htmlspecialchars($p['country_code']) ?></span>
        <?php endif; ?>
    </div>

    <div id="place-map" style="width:100%; height:180px; border-radius:var(--radius-lg); margin-bottom:var(--space-4);"
         data-lat="<?= htmlspecialchars((string) $p['lat']) ?>"
         data-lng="<?= htmlspecialchars((string) $p['lng']) ?>">
    </div>

    <div class="place-detail__coords text-sm text-muted mb-4">
        <?= htmlspecialchars((string) $p['lat']) ?>, <?= htmlspecialchars((string) $p['lng']) ?>
        <?php if ($p['address_text']): ?>
            <br><?= htmlspecialchars($p['address_text']) ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($tags)): ?>
        <div class="place-detail__tags mb-4">
            <?php foreach ($tags as $tag): ?>
                <span class="tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="place-detail__actions flex gap-3 mb-6">
        <a href="/platser/<?= htmlspecialchars($p['slug']) ?>/besok/nytt" class="btn btn-primary">+ Nytt besök</a>
        <a href="/platser/<?= htmlspecialchars($p['slug']) ?>/redigera" class="btn btn-secondary">Redigera</a>
    </div>

    <h3 class="mb-4">Besök (<?= count($visits) ?>)</h3>

    <?php if (empty($visits)): ?>
        <p class="text-muted">Inga besök ännu.</p>
    <?php else: ?>
        <?php foreach ($visits as $visit): ?>
            <div class="visit-card mb-3">
                <div class="flex-between">
                    <span class="visit-card__date"><?= htmlspecialchars($visit['visited_at']) ?></span>
                    <?php if ($visit['total_rating_cached']): ?>
                        <span class="visit-card__rating">&#9733; <?= number_format((float) $visit['total_rating_cached'], 1) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($visit['raw_note']): ?>
                    <p class="visit-card__note text-sm mt-2"><?= nl2br(htmlspecialchars(mb_strimwidth($visit['raw_note'], 0, 200, '...'))) ?></p>
                <?php endif; ?>
                <a href="/besok/<?= $visit['id'] ?>" class="text-sm" style="color:var(--color-accent);">Visa besök &rarr;</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/js/map.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('place-map');
    if (el) {
        initStaticMap(el, parseFloat(el.dataset.lat), parseFloat(el.dataset.lng));
    }
});
</script>
```

- [ ] **Step 5: Create `views/places/edit.php`**

```php
<div class="page-header mb-4">
    <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera <?= htmlspecialchars($p['name']) ?></h2>
</div>

<form method="POST" action="/platser/<?= htmlspecialchars($p['slug']) ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="form-group">
        <label for="name" class="form-label">Namn *</label>
        <input type="text" id="name" name="name" class="form-input" required value="<?= htmlspecialchars($p['name']) ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Typ</label>
        <div class="chip-row" style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
            <?php
            $types = [
                'stellplatz'=>'Ställplats','camping'=>'Camping','wild_camping'=>'Vildcamping',
                'fika'=>'Fika','lunch'=>'Lunch','dinner'=>'Middag','breakfast'=>'Frukost',
                'sight'=>'Sevärdhet','shopping'=>'Shopping'
            ];
            foreach ($types as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="place_type" value="<?= $val ?>" <?= $p['place_type'] === $val ? 'checked' : '' ?>>
                    <span class="chip chip--<?= $val ?>"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="lat" class="form-label">Latitud</label>
        <input type="number" id="lat" name="lat" class="form-input" step="0.0000001" value="<?= htmlspecialchars((string) $p['lat']) ?>">
    </div>

    <div class="form-group">
        <label for="lng" class="form-label">Longitud</label>
        <input type="number" id="lng" name="lng" class="form-input" step="0.0000001" value="<?= htmlspecialchars((string) $p['lng']) ?>">
    </div>

    <div class="form-group">
        <label for="address_text" class="form-label">Adress</label>
        <input type="text" id="address_text" name="address_text" class="form-input" value="<?= htmlspecialchars($p['address_text'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="country_code" class="form-label">Land</label>
        <select id="country_code" name="country_code" class="form-select">
            <option value="">Välj land</option>
            <?php
            $countries = ['SE'=>'Sverige','NO'=>'Norge','DK'=>'Danmark','FI'=>'Finland','DE'=>'Tyskland','FR'=>'Frankrike','IT'=>'Italien','ES'=>'Spanien','PT'=>'Portugal','NL'=>'Nederländerna','BE'=>'Belgien','AT'=>'Österrike','CH'=>'Schweiz','PL'=>'Polen','CZ'=>'Tjeckien','HR'=>'Kroatien','GR'=>'Grekland'];
            foreach ($countries as $code => $name): ?>
                <option value="<?= $code ?>" <?= ($p['country_code'] ?? '') === $code ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/platser/<?= htmlspecialchars($p['slug']) ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Är du säker? Alla besök tas också bort.')">Ta bort plats</button>
</form>
```

- [ ] **Step 6: Verify PHP syntax**

```bash
php -l views/places/index.php && php -l views/places/show.php && php -l views/places/create.php && php -l views/places/edit.php && php -l views/partials/place-card.php
```
Expected: `No syntax errors detected`

- [ ] **Step 7: Commit**

```bash
git add views/places/ views/partials/place-card.php
git commit -m "feat: place views — index, create, show, edit with GPS map and Swedish UI"
```

---

## Task 10: Visit model, controller, and views

**Files:**
- Create: `app/Models/Visit.php`
- Create: `app/Models/VisitRating.php`
- Create: `app/Models/VisitImage.php`
- Create: `app/Controllers/VisitController.php`
- Create: `views/visits/create.php`
- Create: `views/visits/show.php`
- Create: `views/visits/edit.php`
- Create: `views/partials/visit-card.php`
- Create: `views/partials/rating-display.php`

- [ ] **Step 1: Create `app/Models/Visit.php`**

```php
<?php

declare(strict_types=1);

class Visit
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT v.*, p.name as place_name, p.slug as place_slug, p.place_type
            FROM visits v
            JOIN places p ON p.id = v.place_id
            WHERE v.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByPlace(int $placeId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ?
            ORDER BY v.visited_at DESC
        ');
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO visits (place_id, user_id, visited_at, raw_note, plus_notes, minus_notes,
                tips_notes, price_level, would_return, suitable_for, things_to_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['place_id'],
            $data['user_id'],
            $data['visited_at'],
            $data['raw_note'] ?? null,
            $data['plus_notes'] ?? null,
            $data['minus_notes'] ?? null,
            $data['tips_notes'] ?? null,
            $data['price_level'] ?? null,
            $data['would_return'] ?? null,
            $data['suitable_for'] ?? null,
            $data['things_to_note'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE visits SET visited_at = ?, raw_note = ?, plus_notes = ?, minus_notes = ?,
                tips_notes = ?, price_level = ?, would_return = ?, suitable_for = ?,
                things_to_note = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['visited_at'],
            $data['raw_note'] ?? null,
            $data['plus_notes'] ?? null,
            $data['minus_notes'] ?? null,
            $data['tips_notes'] ?? null,
            $data['price_level'] ?? null,
            $data['would_return'] ?? null,
            $data['suitable_for'] ?? null,
            $data['things_to_note'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM visits WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function recentForUser(int $userId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('
            SELECT v.*, p.name as place_name, p.slug as place_slug, vr.total_rating_cached
            FROM visits v
            JOIN places p ON p.id = v.place_id
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.user_id = ?
            ORDER BY v.visited_at DESC
            LIMIT ?
        ');
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function suitableForValues(): array
    {
        $stmt = $this->pdo->query('SELECT DISTINCT suitable_for FROM visits WHERE suitable_for IS NOT NULL AND suitable_for != ""');
        $values = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $row) {
            foreach (explode(',', $row) as $val) {
                $trimmed = trim($val);
                if ($trimmed !== '') {
                    $values[$trimmed] = true;
                }
            }
        }
        ksort($values);
        return array_keys($values);
    }
}
```

- [ ] **Step 2: Create `app/Models/VisitRating.php`**

```php
<?php

declare(strict_types=1);

class VisitRating
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByVisit(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visit_ratings WHERE visit_id = ?');
        $stmt->execute([$visitId]);
        return $stmt->fetch() ?: null;
    }

    public function save(int $visitId, array $ratings): void
    {
        $fields = ['location_rating', 'calmness_rating', 'service_rating', 'value_rating', 'return_value_rating'];
        $provided = array_filter($ratings, fn($v) => $v !== null && $v !== '');
        $count = count($provided);
        $total = $count > 0 ? round(array_sum($provided) / $count, 1) : null;

        $existing = $this->findByVisit($visitId);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE visit_ratings SET location_rating = ?, calmness_rating = ?, service_rating = ?,
                    value_rating = ?, return_value_rating = ?, total_rating_cached = ?, updated_at = NOW()
                WHERE visit_id = ?
            ');
            $stmt->execute([
                $ratings['location_rating'] ?? null,
                $ratings['calmness_rating'] ?? null,
                $ratings['service_rating'] ?? null,
                $ratings['value_rating'] ?? null,
                $ratings['return_value_rating'] ?? null,
                $total,
                $visitId,
            ]);
        } else {
            $stmt = $this->pdo->prepare('
                INSERT INTO visit_ratings (visit_id, location_rating, calmness_rating, service_rating,
                    value_rating, return_value_rating, total_rating_cached)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $visitId,
                $ratings['location_rating'] ?? null,
                $ratings['calmness_rating'] ?? null,
                $ratings['service_rating'] ?? null,
                $ratings['value_rating'] ?? null,
                $ratings['return_value_rating'] ?? null,
                $total,
            ]);
        }
    }
}
```

- [ ] **Step 3: Create `app/Models/VisitImage.php`**

```php
<?php

declare(strict_types=1);

class VisitImage
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visit_images WHERE visit_id = ? ORDER BY image_order ASC');
        $stmt->execute([$visitId]);
        return $stmt->fetchAll();
    }

    public function create(int $visitId, string $filename, string $originalName, string $mimeType, int $fileSize, int $order): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO visit_images (visit_id, filename, original_name, mime_type, file_size, image_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$visitId, $filename, $originalName, $mimeType, $fileSize, $order]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT filename FROM visit_images WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            $del = $this->pdo->prepare('DELETE FROM visit_images WHERE id = ?');
            $del->execute([$id]);
            return $row['filename'];
        }
        return null;
    }
}
```

- [ ] **Step 4: Create `app/Controllers/VisitController.php`**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/ImageService.php';
require_once dirname(__DIR__) . '/Models/Place.php';
require_once dirname(__DIR__) . '/Models/Visit.php';
require_once dirname(__DIR__) . '/Models/VisitRating.php';
require_once dirname(__DIR__) . '/Models/VisitImage.php';

class VisitController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $placeModel = new Place($this->pdo);
        $p = $placeModel->findBySlug($params['slug']);

        if (!$p) {
            http_response_code(404);
            return;
        }

        $visitModel = new Visit($this->pdo);
        $suitableForSuggestions = $visitModel->suitableForValues();

        $pageTitle = 'Nytt besök — ' . $p['name'];
        view('visits/create', compact('p', 'pageTitle', 'suitableForSuggestions'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $p = $placeModel->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); return; }

        $visitModel = new Visit($this->pdo);
        $visitId = $visitModel->create([
            'place_id'       => $p['id'],
            'user_id'        => Auth::userId(),
            'visited_at'     => $_POST['visited_at'] ?? date('Y-m-d'),
            'raw_note'       => trim($_POST['raw_note'] ?? '') ?: null,
            'plus_notes'     => trim($_POST['plus_notes'] ?? '') ?: null,
            'minus_notes'    => trim($_POST['minus_notes'] ?? '') ?: null,
            'tips_notes'     => trim($_POST['tips_notes'] ?? '') ?: null,
            'price_level'    => $_POST['price_level'] ?? null,
            'would_return'   => $_POST['would_return'] ?? null,
            'suitable_for'   => trim($_POST['suitable_for'] ?? '') ?: null,
            'things_to_note' => trim($_POST['things_to_note'] ?? '') ?: null,
        ]);

        // Save ratings
        $ratingModel = new VisitRating($this->pdo);
        $ratingModel->save($visitId, [
            'location_rating'     => $_POST['location_rating'] ? (int) $_POST['location_rating'] : null,
            'calmness_rating'     => $_POST['calmness_rating'] ? (int) $_POST['calmness_rating'] : null,
            'service_rating'      => $_POST['service_rating'] ? (int) $_POST['service_rating'] : null,
            'value_rating'        => $_POST['value_rating'] ? (int) $_POST['value_rating'] : null,
            'return_value_rating' => $_POST['return_value_rating'] ? (int) $_POST['return_value_rating'] : null,
        ]);

        // Handle image uploads
        if (!empty($_FILES['photos']['name'][0])) {
            $imageService = new ImageService($this->config);
            $imageModel = new VisitImage($this->pdo);
            $files = $_FILES['photos'];

            for ($i = 0; $i < min(count($files['name']), 8); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $result = $imageService->upload($files['tmp_name'][$i], $files['name'][$i], $files['type'][$i], $files['size'][$i]);
                if ($result) {
                    $imageModel->create($visitId, $result['filename'], $files['name'][$i], $files['type'][$i], $files['size'][$i], $i);
                }
            }
        }

        flash('success', 'Besöket har sparats!');
        redirect('/platser/' . $p['slug']);
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { http_response_code(404); return; }

        $ratingModel = new VisitRating($this->pdo);
        $ratings = $ratingModel->findByVisit((int) $params['id']);

        $imageModel = new VisitImage($this->pdo);
        $images = $imageModel->findByVisit((int) $params['id']);

        $pageTitle = 'Besök — ' . $visit['place_name'];
        view('visits/show', compact('visit', 'ratings', 'images', 'pageTitle'));
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { http_response_code(404); return; }

        $ratingModel = new VisitRating($this->pdo);
        $ratings = $ratingModel->findByVisit((int) $params['id']);

        $suitableForSuggestions = $visitModel->suitableForValues();

        $pageTitle = 'Redigera besök';
        view('visits/edit', compact('visit', 'ratings', 'pageTitle', 'suitableForSuggestions'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { http_response_code(404); return; }

        $visitModel->update((int) $params['id'], [
            'visited_at'     => $_POST['visited_at'] ?? $visit['visited_at'],
            'raw_note'       => trim($_POST['raw_note'] ?? '') ?: null,
            'plus_notes'     => trim($_POST['plus_notes'] ?? '') ?: null,
            'minus_notes'    => trim($_POST['minus_notes'] ?? '') ?: null,
            'tips_notes'     => trim($_POST['tips_notes'] ?? '') ?: null,
            'price_level'    => $_POST['price_level'] ?? null,
            'would_return'   => $_POST['would_return'] ?? null,
            'suitable_for'   => trim($_POST['suitable_for'] ?? '') ?: null,
            'things_to_note' => trim($_POST['things_to_note'] ?? '') ?: null,
        ]);

        $ratingModel = new VisitRating($this->pdo);
        $ratingModel->save((int) $params['id'], [
            'location_rating'     => $_POST['location_rating'] ? (int) $_POST['location_rating'] : null,
            'calmness_rating'     => $_POST['calmness_rating'] ? (int) $_POST['calmness_rating'] : null,
            'service_rating'      => $_POST['service_rating'] ? (int) $_POST['service_rating'] : null,
            'value_rating'        => $_POST['value_rating'] ? (int) $_POST['value_rating'] : null,
            'return_value_rating' => $_POST['return_value_rating'] ? (int) $_POST['return_value_rating'] : null,
        ]);

        flash('success', 'Besöket har uppdaterats.');
        redirect('/besok/' . $params['id']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { redirect('/platser'); return; }

        $visitModel->delete((int) $params['id']);
        flash('success', 'Besöket har tagits bort.');
        redirect('/platser/' . $visit['place_slug']);
    }

    public function uploadImage(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (empty($_FILES['photo'])) {
            echo json_encode(['error' => 'Ingen fil']);
            return;
        }

        $imageService = new ImageService($this->config);
        $file = $_FILES['photo'];
        $result = $imageService->upload($file['tmp_name'], $file['name'], $file['type'], $file['size']);

        if ($result) {
            echo json_encode(['success' => true, 'filename' => $result['filename']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Uppladdningen misslyckades']);
        }
    }

    public function suitableForSuggestions(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $visitModel = new Visit($this->pdo);
        echo json_encode($visitModel->suitableForValues());
    }
}
```

- [ ] **Step 5: Create `views/visits/create.php`**

```php
<div class="page-header mb-4">
    <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn-ghost btn--sm">&larr; <?= htmlspecialchars($p['name']) ?></a>
    <h2>Nytt besök</h2>
</div>

<form method="POST" action="/platser/<?= htmlspecialchars($p['slug']) ?>/besok" enctype="multipart/form-data" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="visited_at" class="form-label">Datum *</label>
        <input type="date" id="visited_at" name="visited_at" class="form-input" value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="form-group">
        <label for="raw_note" class="form-label">Anteckning</label>
        <textarea id="raw_note" name="raw_note" class="form-textarea form-textarea--note" rows="4" placeholder="Skriv vad du vill..."></textarea>
    </div>

    <details class="mb-4">
        <summary class="btn btn-ghost btn--sm" style="cursor:pointer;">+ Strukturerade fält</summary>
        <div style="padding-top:var(--space-4);">
            <div class="form-group">
                <label for="plus_notes" class="form-label">Vad var bra?</label>
                <textarea id="plus_notes" name="plus_notes" class="form-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="minus_notes" class="form-label">Vad var dåligt?</label>
                <textarea id="minus_notes" name="minus_notes" class="form-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="tips_notes" class="form-label">Tips</label>
                <textarea id="tips_notes" name="tips_notes" class="form-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Prisnivå</label>
                <div class="flex gap-2">
                    <?php foreach (['free'=>'Gratis','low'=>'€','medium'=>'€€','high'=>'€€€'] as $val => $label): ?>
                        <label class="chip-option">
                            <input type="radio" name="price_level" value="<?= $val ?>">
                            <span class="chip"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Skulle återvända?</label>
                <div class="flex gap-2">
                    <?php foreach (['yes'=>'Ja','maybe'=>'Kanske','no'=>'Nej'] as $val => $label): ?>
                        <label class="chip-option">
                            <input type="radio" name="would_return" value="<?= $val ?>">
                            <span class="chip"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="suitable_for" class="form-label">Passar för</label>
                <input type="text" id="suitable_for" name="suitable_for" class="form-input" placeholder="t.ex. husbilar, hundar, familjer"
                    data-suggestions='<?= htmlspecialchars(json_encode($suitableForSuggestions)) ?>'>
                <span class="form-hint">Kommaseparerat. Tidigare använda förslag visas.</span>
            </div>
            <div class="form-group">
                <label for="things_to_note" class="form-label">Att notera</label>
                <textarea id="things_to_note" name="things_to_note" class="form-textarea" rows="2"></textarea>
            </div>
        </div>
    </details>

    <div class="form-group">
        <label class="form-label">Betyg</label>
        <?php
        $ratingLabels = ['location_rating'=>'Läge','calmness_rating'=>'Lugn','service_rating'=>'Service','value_rating'=>'Värde','return_value_rating'=>'Återkomst'];
        foreach ($ratingLabels as $field => $label): ?>
            <div class="rating-input-row flex-between mb-2">
                <span class="text-sm"><?= $label ?></span>
                <div class="rating-input flex gap-1" data-field="<?= $field ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="rating-dot" data-value="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                    <input type="hidden" name="<?= $field ?>" value="">
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <label class="form-label">Foton (max 8)</label>
        <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-input">
    </div>

    <button type="submit" class="btn btn-primary btn--full">Spara besök</button>
</form>

<script src="/js/ratings.js"></script>
<script src="/js/tags.js"></script>
```

- [ ] **Step 6: Create `views/visits/show.php` and `views/visits/edit.php`**

`views/visits/show.php`:
```php
<div class="page-header mb-4">
    <a href="/platser/<?= htmlspecialchars($visit['place_slug']) ?>" class="btn-ghost btn--sm">&larr; <?= htmlspecialchars($visit['place_name']) ?></a>
</div>

<div class="visit-detail">
    <h2>Besök <?= htmlspecialchars($visit['visited_at']) ?></h2>
    <p class="text-sm text-muted mb-4"><?= htmlspecialchars($visit['place_name']) ?></p>

    <?php if ($visit['raw_note']): ?>
        <div class="visit-detail__note mb-4" style="background:#FDFCF8; padding:var(--space-4); border-radius:var(--radius-md); border:1px solid var(--color-warm-dark);">
            <?= nl2br(htmlspecialchars($visit['raw_note'])) ?>
        </div>
    <?php endif; ?>

    <?php if ($visit['plus_notes'] || $visit['minus_notes'] || $visit['tips_notes']): ?>
        <div class="visit-detail__fields mb-4">
            <?php if ($visit['plus_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Plus:</strong> <?= nl2br(htmlspecialchars($visit['plus_notes'])) ?></div>
            <?php endif; ?>
            <?php if ($visit['minus_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Minus:</strong> <?= nl2br(htmlspecialchars($visit['minus_notes'])) ?></div>
            <?php endif; ?>
            <?php if ($visit['tips_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Tips:</strong> <?= nl2br(htmlspecialchars($visit['tips_notes'])) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($visit['price_level'] || $visit['would_return'] || $visit['suitable_for']): ?>
        <div class="visit-detail__meta mb-4 text-sm">
            <?php if ($visit['price_level']): ?>
                <div>Pris: <?= htmlspecialchars($visit['price_level']) ?></div>
            <?php endif; ?>
            <?php if ($visit['would_return']): ?>
                <div>Återvända: <?= ['yes'=>'Ja','maybe'=>'Kanske','no'=>'Nej'][$visit['would_return']] ?? '' ?></div>
            <?php endif; ?>
            <?php if ($visit['suitable_for']): ?>
                <div>Passar för: <?= htmlspecialchars($visit['suitable_for']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($ratings): ?>
        <?php include dirname(__DIR__) . '/partials/rating-display.php'; ?>
    <?php endif; ?>

    <?php if (!empty($images)): ?>
        <div class="visit-detail__gallery mb-4" style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
            <?php foreach ($images as $img): ?>
                <img src="/uploads/cards/<?= htmlspecialchars($img['filename']) ?>" alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                     style="width:120px; height:90px; object-fit:cover; border-radius:var(--radius-md);">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="flex gap-3 mt-6">
        <a href="/besok/<?= $visit['id'] ?>/redigera" class="btn btn-secondary btn--sm">Redigera</a>
        <form method="POST" action="/besok/<?= $visit['id'] ?>" style="display:inline;">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Ta bort besöket?')">Ta bort</button>
        </form>
    </div>
</div>
```

`views/visits/edit.php` — same form structure as create.php but pre-populated with `$visit` and `$ratings` data, POST to `/besok/{id}` with `_method=PUT`. (Copy create.php and add `value="<?= htmlspecialchars($visit['field'] ?? '') ?>"` to each field.)

- [ ] **Step 7: Create `views/partials/rating-display.php`**

```php
<?php if ($ratings): ?>
<div class="rating-display">
    <?php
    $ratingLabels = ['location_rating'=>'Läge','calmness_rating'=>'Lugn','service_rating'=>'Service','value_rating'=>'Värde','return_value_rating'=>'Återkomst'];
    foreach ($ratingLabels as $field => $label):
        $val = $ratings[$field] ?? null;
        if ($val === null) continue;
    ?>
        <div class="rating-display__row flex-between text-sm mb-1">
            <span><?= $label ?></span>
            <span class="rating-dots">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="rating-dot--display <?= $i <= $val ? 'rating-dot--filled' : '' ?>"></span>
                <?php endfor; ?>
                <span class="text-xs text-muted" style="margin-left:4px;"><?= $val ?></span>
            </span>
        </div>
    <?php endforeach; ?>

    <?php if ($ratings['total_rating_cached']): ?>
        <div class="rating-display__total flex-between mt-2" style="border-top:1px solid var(--color-border); padding-top:var(--space-2);">
            <strong>Totalt</strong>
            <span>&#9733; <?= number_format((float) $ratings['total_rating_cached'], 1) ?></span>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
```

- [ ] **Step 8: Verify PHP syntax**

```bash
php -l app/Models/Visit.php && php -l app/Models/VisitRating.php && php -l app/Models/VisitImage.php && php -l app/Controllers/VisitController.php
```
Expected: `No syntax errors detected`

- [ ] **Step 9: Commit**

```bash
git add app/Models/ app/Controllers/VisitController.php views/visits/ views/partials/visit-card.php views/partials/rating-display.php
git commit -m "feat: visit CRUD with ratings, images, structured fields, suitable_for autocomplete"
```

---

## Task 11: Image service

**Files:**
- Create: `app/Services/ImageService.php`

- [ ] **Step 1: Create `app/Services/ImageService.php`**

```php
<?php

declare(strict_types=1);

class ImageService
{
    private array $config;
    private string $basePath;

    private const VARIANTS = [
        'thumbnails' => [150, 150],
        'cards'      => [400, 300],
        'detail'     => [1200, 900],
    ];

    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->basePath = dirname(__DIR__, 2) . '/storage/uploads';
    }

    public function upload(string $tmpPath, string $originalName, string $mimeType, int $fileSize): ?array
    {
        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return null;
        }

        if ($fileSize > $this->config['upload_max_size']) {
            return null;
        }

        // Verify it's actually an image
        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo === false) {
            return null;
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        // Save original
        $originalDest = $this->basePath . '/originals/' . $filename;
        if (!move_uploaded_file($tmpPath, $originalDest)) {
            return null;
        }

        // Generate variants
        foreach (self::VARIANTS as $dir => [$maxW, $maxH]) {
            $this->resize($originalDest, $this->basePath . '/' . $dir . '/' . $filename, $maxW, $maxH, $mimeType);
        }

        return ['filename' => $filename];
    }

    private function resize(string $source, string $dest, int $maxW, int $maxH, string $mimeType): void
    {
        $img = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($source),
            'image/png'  => imagecreatefrompng($source),
            'image/webp' => imagecreatefromwebp($source),
            default      => null,
        };

        if (!$img) return;

        $origW = imagesx($img);
        $origH = imagesy($img);

        $ratio = min($maxW / $origW, $maxH / $origH);
        if ($ratio >= 1) {
            // Image is smaller than target, just copy
            $ratio = 1;
        }

        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);

        $resized = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        match ($mimeType) {
            'image/jpeg' => imagejpeg($resized, $dest, 85),
            'image/png'  => imagepng($resized, $dest, 6),
            'image/webp' => imagewebp($resized, $dest, 85),
            default      => null,
        };

        imagedestroy($img);
        imagedestroy($resized);
    }

    public function delete(string $filename): void
    {
        $dirs = ['originals', 'thumbnails', 'cards', 'detail'];
        foreach ($dirs as $dir) {
            $path = $this->basePath . '/' . $dir . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l app/Services/ImageService.php
```
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/Services/ImageService.php
git commit -m "feat: image upload service with resize variants (thumbnail, card, detail)"
```

---

## Task 12: JavaScript — GPS, map, ratings, tags, app

**Files:**
- Create: `public/js/app.js`
- Create: `public/js/gps.js`
- Create: `public/js/map.js`
- Create: `public/js/ratings.js`
- Create: `public/js/tags.js`
- Create: `public/js/gallery.js`

- [ ] **Step 1: Create `public/js/app.js`**

```javascript
// Frizon.org — Global JS

// Auto-dismiss toasts
document.querySelectorAll('.toast').forEach(function(toast) {
    var delay = toast.classList.contains('toast--error') ? 8000 : 4000;
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(function() { toast.remove(); }, 200);
    }, delay);
});

// CSRF token for fetch requests
function getCsrfToken() {
    var el = document.querySelector('input[name="_csrf"]');
    return el ? el.value : '';
}
```

- [ ] **Step 2: Create `public/js/gps.js`**

```javascript
// GPS capture for place creation
function initGpsCapture(mapId, latId, lngId) {
    var mapEl = document.getElementById(mapId);
    var latInput = document.getElementById(latId);
    var lngInput = document.getElementById(lngId);

    if (!mapEl || !navigator.geolocation) {
        mapEl.innerHTML = '<p style="padding:1rem; text-align:center; color:#4A6070;">GPS är inte tillgänglig.</p>';
        return;
    }

    // Default: Sweden center
    var map = L.map(mapId).setView([59.33, 18.07], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var marker = null;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;

            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);

            map.setView([lat, lng], 15);
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            marker.on('dragend', function() {
                var p = marker.getLatLng();
                latInput.value = p.lat.toFixed(7);
                lngInput.value = p.lng.toFixed(7);
            });

            // Check for nearby places
            fetch('/api/platser/nearby?lat=' + lat + '&lng=' + lng)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.places && data.places.length > 0) {
                        var p = data.places[0];
                        var dist = Math.round(p.distance_meters);
                        if (confirm('Det ser ut som att du är vid ' + p.name + ' (' + dist + ' m bort).\n\nSkapa ett nytt besök istället?')) {
                            window.location.href = '/platser/' + p.slug + '/besok/nytt';
                        }
                    }
                });
        },
        function(err) {
            mapEl.innerHTML = '<p style="padding:1rem; text-align:center; color:#4A6070;">Kunde inte hämta position. Ange koordinater manuellt.</p>';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );

    // Click on map to set position
    map.on('click', function(e) {
        latInput.value = e.latlng.lat.toFixed(7);
        lngInput.value = e.latlng.lng.toFixed(7);
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            marker.on('dragend', function() {
                var p = marker.getLatLng();
                latInput.value = p.lat.toFixed(7);
                lngInput.value = p.lng.toFixed(7);
            });
        }
    });
}
```

- [ ] **Step 3: Create `public/js/map.js`**

```javascript
// Static map for place detail
function initStaticMap(el, lat, lng) {
    var map = L.map(el).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
}
```

- [ ] **Step 4: Create `public/js/ratings.js`**

```javascript
// Rating input dots
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.rating-input').forEach(function(group) {
        var input = group.querySelector('input[type="hidden"]');
        var dots = group.querySelectorAll('.rating-dot');

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                var val = parseInt(this.dataset.value);
                input.value = val;

                dots.forEach(function(d) {
                    var dv = parseInt(d.dataset.value);
                    d.classList.toggle('rating-dot--active', dv <= val);
                });
            });
        });
    });
});
```

- [ ] **Step 5: Create `public/js/tags.js`**

```javascript
// Tag autocomplete for suitable_for field
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('suitable_for');
    if (!input || !input.dataset.suggestions) return;

    var suggestions = JSON.parse(input.dataset.suggestions);
    var dropdown = document.createElement('div');
    dropdown.className = 'tag-autocomplete';
    dropdown.style.display = 'none';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(dropdown);

    input.addEventListener('input', function() {
        var parts = this.value.split(',');
        var current = parts[parts.length - 1].trim().toLowerCase();

        if (current.length < 1) {
            dropdown.style.display = 'none';
            return;
        }

        var matches = suggestions.filter(function(s) {
            return s.toLowerCase().indexOf(current) !== -1;
        }).slice(0, 5);

        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        dropdown.innerHTML = matches.map(function(m) {
            return '<div class="tag-autocomplete__item">' + m + '</div>';
        }).join('');
        dropdown.style.display = 'block';

        dropdown.querySelectorAll('.tag-autocomplete__item').forEach(function(item) {
            item.addEventListener('click', function() {
                parts[parts.length - 1] = ' ' + this.textContent;
                input.value = parts.join(',');
                dropdown.style.display = 'none';
                input.focus();
            });
        });
    });

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
```

- [ ] **Step 6: Create `public/js/gallery.js`**

```javascript
// Image upload preview
document.addEventListener('DOMContentLoaded', function() {
    var fileInput = document.querySelector('input[type="file"][name="photos[]"]');
    if (!fileInput) return;

    fileInput.addEventListener('change', function() {
        var preview = this.parentNode.querySelector('.upload-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'upload-preview';
            preview.style.cssText = 'display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;';
            this.parentNode.appendChild(preview);
        }
        preview.innerHTML = '';

        var files = Array.from(this.files).slice(0, 8);
        files.forEach(function(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:80px; height:80px; object-fit:cover; border-radius:6px;';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
});
```

- [ ] **Step 7: Commit**

```bash
git add public/js/
git commit -m "feat: GPS capture, Leaflet maps, rating input, tag autocomplete, image preview"
```

---

## Task 13: Dashboard controller and view

**Files:**
- Create: `app/Controllers/DashboardController.php`
- Create: `views/dashboard/index.php`

- [ ] **Step 1: Create `app/Controllers/DashboardController.php`**

```php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Models/Visit.php';

class DashboardController
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

        $visitModel = new Visit($this->pdo);
        $recentVisits = $visitModel->recentForUser(Auth::userId(), 5);

        $stats = [];
        $stats['places'] = (int) $this->pdo->query('SELECT COUNT(*) FROM places')->fetchColumn();
        $stats['visits'] = (int) $this->pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
        $stats['countries'] = (int) $this->pdo->query('SELECT COUNT(DISTINCT country_code) FROM places WHERE country_code IS NOT NULL')->fetchColumn();

        $pageTitle = 'Dashboard';
        view('dashboard/index', compact('recentVisits', 'stats', 'pageTitle'));
    }
}
```

- [ ] **Step 2: Create `views/dashboard/index.php`**

```php
<div class="dashboard">
    <h2 class="mb-2">Hej <?= htmlspecialchars(Auth::userName() ?? '') ?>!</h2>
    <p class="text-muted text-sm mb-6" style="font-family:var(--font-script); font-size:1.1rem;">Frizon of Sweden</p>

    <div class="dashboard-stats mb-6">
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['places'] ?></div>
            <div class="stat-card__label">Platser</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['visits'] ?></div>
            <div class="stat-card__label">Besök</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['countries'] ?></div>
            <div class="stat-card__label">Länder</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number">0</div>
            <div class="stat-card__label">Resor</div>
        </div>
    </div>

    <h3 class="mb-4">Senaste besök</h3>
    <?php if (empty($recentVisits)): ?>
        <div class="empty-state text-center" style="padding:var(--space-8) 0;">
            <p class="text-muted mb-4">Inga besök ännu.</p>
            <a href="/platser/ny" class="btn btn-primary">Spara din första plats</a>
        </div>
    <?php else: ?>
        <?php foreach ($recentVisits as $visit): ?>
            <div class="visit-card mb-3">
                <div class="flex-between">
                    <a href="/platser/<?= htmlspecialchars($visit['place_slug']) ?>" style="font-weight:var(--weight-semibold); color:var(--color-brand-dark);">
                        <?= htmlspecialchars($visit['place_name']) ?>
                    </a>
                    <span class="text-xs text-muted"><?= htmlspecialchars($visit['visited_at']) ?></span>
                </div>
                <?php if ($visit['total_rating_cached']): ?>
                    <span class="text-sm">&#9733; <?= number_format((float) $visit['total_rating_cached'], 1) ?></span>
                <?php endif; ?>
                <?php if ($visit['raw_note']): ?>
                    <p class="text-sm text-muted mt-1"><?= htmlspecialchars(mb_strimwidth($visit['raw_note'], 0, 100, '...')) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
```

- [ ] **Step 3: Verify PHP syntax**

```bash
php -l app/Controllers/DashboardController.php && php -l views/dashboard/index.php
```
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/DashboardController.php views/dashboard/
git commit -m "feat: dashboard with stats and recent visits"
```

---

## Task 14: Place radius detection test

**Files:**
- Create: `tests/test_place_radius.php`

- [ ] **Step 1: Create `tests/test_place_radius.php`**

```php
<?php
/**
 * Test: Place nearby detection using Haversine formula.
 * Run: php tests/test_place_radius.php
 */

function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

$tests = [
    // [name, lat1, lng1, lat2, lng2, expected_within_100m]
    ['Same point', 59.3293, 18.0686, 59.3293, 18.0686, true],
    ['50m apart (approx)', 59.3293, 18.0686, 59.3297, 18.0686, true],
    ['500m apart', 59.3293, 18.0686, 59.3338, 18.0686, false],
    ['10km apart', 59.3293, 18.0686, 59.4200, 18.0686, false],
    ['Hammarö to Karlstad (~12km)', 59.3299, 13.5227, 59.3793, 13.5036, false],
];

$passed = 0;
$failed = 0;

foreach ($tests as [$name, $lat1, $lng1, $lat2, $lng2, $expectedWithin]) {
    $dist = haversineDistance($lat1, $lng1, $lat2, $lng2);
    $isWithin = $dist <= 100;
    $ok = $isWithin === $expectedWithin;

    if ($ok) {
        echo "PASS: {$name} — {$dist:.1f}m (within 100m: " . ($isWithin ? 'yes' : 'no') . ")\n";
        $passed++;
    } else {
        echo "FAIL: {$name} — {$dist:.1f}m (expected within=" . ($expectedWithin ? 'yes' : 'no') . ", got=" . ($isWithin ? 'yes' : 'no') . ")\n";
        $failed++;
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

- [ ] **Step 2: Run the test**

```bash
php tests/test_place_radius.php
```
Expected: `5 passed, 0 failed`

- [ ] **Step 3: Commit**

```bash
git add tests/
git commit -m "test: place detection radius using Haversine formula"
```

---

## Task 15: Wire up bootstrap requires and final integration

**Files:**
- Modify: `app/bootstrap.php`
- Modify: `public/index.php`

- [ ] **Step 1: Update `app/bootstrap.php` to load all required files**

Add service requires after the helpers:
```php
require __DIR__ . '/Services/CsrfService.php';
require __DIR__ . '/Services/Auth.php';
```

- [ ] **Step 2: Update `public/index.php` to serve static uploads**

Add before the router dispatch, a rule to serve uploaded images:
```php
// Serve uploaded images from storage
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/uploads/(thumbnails|cards|detail|originals)/(.+)$#', $uri, $m)) {
    $filePath = dirname(__DIR__) . '/storage/uploads/' . $m[1] . '/' . $m[2];
    if (file_exists($filePath)) {
        $mime = mime_content_type($filePath);
        header('Content-Type: ' . $mime);
        readfile($filePath);
        exit;
    }
}
```

- [ ] **Step 3: Run PHP syntax check on all PHP files**

```bash
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"
```
Expected: No output (all files pass)

- [ ] **Step 4: Commit**

```bash
git add app/bootstrap.php public/index.php
git commit -m "feat: wire up services in bootstrap, serve uploaded images"
```

- [ ] **Step 5: Final Phase 1 commit**

```bash
git add -A
git commit -m "feat: Phase 1 complete — auth, places, visits, images, GPS capture, design system"
```

---

## Summary

Phase 1 delivers:
- Project skeleton with MVC structure
- MySQL schema (users, places, visits, ratings, images)
- Session auth with CSRF protection
- Place CRUD with slug-based URLs
- Visit CRUD with structured fields, ratings, and image upload
- GPS-based place creation with nearby detection
- Complete CSS design system from the Frizon graphic profile
- Leaflet maps for place views
- Swedish UI throughout
- Tag autocomplete for suitable_for field
- Image resize service (thumbnail, card, detail variants)
- Place radius detection test
