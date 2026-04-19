<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/Place.php';

class PublicController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function homepage(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $placeModel = new Place($this->pdo);
        $filterType = $_GET['type'] ?? null;
        $filterCountry = $_GET['country'] ?? null;
        $search = trim((string) ($_GET['q'] ?? ''));
        $places = $placeModel->publicListing([
            'place_type' => $filterType ?: null,
            'country_code' => $filterCountry ?: null,
            'search' => $search !== '' ? $search : null,
        ]);

        // Unique countries and types for filters
        $allPublic = $this->pdo->query('SELECT DISTINCT country_code FROM places WHERE public_allowed = 1 AND country_code IS NOT NULL ORDER BY country_code')->fetchAll(PDO::FETCH_COLUMN);
        $allTypes = $this->pdo->query('SELECT DISTINCT place_type FROM places WHERE public_allowed = 1 ORDER BY place_type')->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = 'Frizon of Sweden — Husbilsresor i Europa';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $seoMeta = [
            'description' => 'Platser vi besökt med Frizze, vår Adria Twin husbil. Ställplatser, campingar, restauranger och sevärdheter i Europa — sett ur ett husbilsperspektiv av Mattias och Ulrica.',
            'og_url'      => $appUrl . '/',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        $schemas = [[
            '@context'       => 'https://schema.org',
            '@type'          => 'WebSite',
            'name'           => 'Frizon of Sweden',
            'url'            => $appUrl,
            'description'    => 'Resedagbok med Frizze — platser vi besökt med vår husbil i Europa.',
            'inLanguage'     => 'sv',
            'author'         => [
                '@type' => 'Person',
                'name'  => 'Mattias & Ulrica',
                'url'   => $appUrl,
            ],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $appUrl . '/?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ]];

        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => 'Frizon of Sweden',
            'url'      => $appUrl . '/',
            'logo'     => $appUrl . '/img/frizon-logo.png',
            'sameAs'   => [
                'https://www.instagram.com/frizon_of_sweden',
                'https://www.facebook.com/frizonofsweden',
                'https://www.youtube.com/@frizon_of_sweden',
            ],
        ];

        // Shop teaser: 3 latest published products
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $shopTeaser = (new AmazonProduct($this->pdo))->latestPublished(3);

        // Upcoming teasered trips
        $upcomingStmt = $this->pdo->prepare('
            SELECT title, teaser_text, start_date
            FROM trips
            WHERE public_teaser = 1 AND start_date > CURDATE()
            ORDER BY start_date ASC
            LIMIT 3
        ');
        $upcomingStmt->execute();
        $upcomingTrips = $upcomingStmt->fetchAll();

        $useLeaflet = true;
        view('public/homepage', compact('places', 'filterType', 'filterCountry', 'allPublic', 'allTypes', 'pageTitle', 'seoMeta', 'schemas', 'shopTeaser', 'upcomingTrips', 'useLeaflet', 'search'), 'public');
    }

    public function placeDetail(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place || !$place['public_allowed']) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
        }

        // Increment view counter (fire-and-forget, ignore failures)
        try {
            $this->pdo->prepare('UPDATE places SET view_count = view_count + 1 WHERE id = ?')
                      ->execute([$place['id']]);
        } catch (PDOException $e) {
            error_log('view_count increment failed: ' . $e->getMessage());
        }

        // Get visits with ratings and images
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached, vr.location_rating, vr.calmness_rating,
                   vr.service_rating, vr.value_rating, vr.return_value_rating
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
            ORDER BY v.visited_at DESC
        ');
        $stmt->execute([$place['id']]);
        $visits = $stmt->fetchAll();

        // Get published images
        $imageStmt = $this->pdo->prepare('
            SELECT vi.* FROM visit_images vi
            JOIN visits v ON v.id = vi.visit_id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
            ORDER BY vi.image_order ASC
            LIMIT 12
        ');
        $imageStmt->execute([$place['id']]);
        $images = $imageStmt->fetchAll();

        // Index image counts per visit
        $visitImageCounts = [];
        foreach ($images as $img) {
            $vid = $img['visit_id'];
            $visitImageCounts[$vid] = ($visitImageCounts[$vid] ?? 0) + 1;
        }

        // Tags
        $tagStmt = $this->pdo->prepare('SELECT tag FROM place_tags WHERE place_id = ?');
        $tagStmt->execute([$place['id']]);
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        // Products linked to this place
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $placeProducts = (new AmazonProduct($this->pdo))->getByPlaceId((int) $place['id']);

        // Avg rating
        $ratingStmt = $this->pdo->prepare('
            SELECT AVG(vr.total_rating_cached) as avg_rating
            FROM visits v
            JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
        ');
        $ratingStmt->execute([$place['id']]);
        $avgRating = $ratingStmt->fetchColumn();

        $pageTitle = $place['name'] . ' — Frizon of Sweden';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        // og:image — first uploaded image or logo fallback
        $ogImage = $appUrl . '/img/frizon-logo.png';
        if (!empty($images)) {
            $ogImage = $appUrl . '/uploads/cards/' . $images[0]['filename'];
        }

        $metaDesc = $place['meta_description']
            ?? ($place['default_public_text'] ? mb_strimwidth($place['default_public_text'], 0, 155, '...') : null)
            ?? $place['name'] . ' — besökt med vår husbil Frizze. Läs vår recension på Frizon of Sweden.';

        $seoMeta = [
            'description' => $metaDesc,
            'og_url'      => $appUrl . '/platser/' . $place['slug'],
            'og_image'    => $ogImage,
        ];

        // TouristAttraction schema
        $placeSchema = [
            '@context' => 'https://schema.org',
            '@type'    => 'TouristAttraction',
            'name'     => $place['name'],
            'url'      => $appUrl . '/platser/' . $place['slug'],
            'geo'      => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $place['lat'],
                'longitude' => (float) $place['lng'],
            ],
        ];
        if ($place['default_public_text']) {
            $placeSchema['description'] = $place['default_public_text'];
        }
        if ($place['country_code']) {
            $placeSchema['address'] = [
                '@type'          => 'PostalAddress',
                'addressCountry' => strtoupper($place['country_code']),
            ];
        }

        // AggregateRating — only when at least one visit has a rating
        if ($avgRating !== null && count($visits) > 0) {
            $placeSchema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $avgRating, 1),
                'bestRating'  => 5,
                'worstRating' => 1,
                'reviewCount' => count($visits),
            ];
        }

        // Review — only visits with both approved text AND a rating
        $reviewItems = [];
        foreach ($visits as $v) {
            if (!empty($v['approved_public_text']) && !empty($v['total_rating_cached'])) {
                $reviewItems[] = [
                    '@type'        => 'Review',
                    'author'       => ['@type' => 'Person', 'name' => 'Mattias & Ulrica'],
                    'datePublished'=> substr((string) $v['visited_at'], 0, 10),
                    'reviewBody'   => $v['approved_public_text'],
                    'reviewRating' => [
                        '@type'       => 'Rating',
                        'ratingValue' => round((float) $v['total_rating_cached'], 1),
                        'bestRating'  => 5,
                        'worstRating' => 1,
                    ],
                ];
            }
        }
        if (!empty($reviewItems)) {
            $placeSchema['review'] = $reviewItems;
        }

        $schemas = [$placeSchema];

        // FAQPage schema — only when faq_content is populated
        $faqItems = !empty($place['faq_content']) ? json_decode((string) $place['faq_content'], true) : [];
        if (!empty($faqItems)) {
            $schemas[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array_map(fn($item) => [
                    '@type'          => 'Question',
                    'name'           => $item['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                ], $faqItems),
            ];
        }

        $previewImage = null;
        if ($place['preview_image_id']) {
            $prevStmt = $this->pdo->prepare('SELECT filename, alt_text FROM visit_images WHERE id = ?');
            $prevStmt->execute([$place['preview_image_id']]);
            $previewImage = $prevStmt->fetch() ?: null;
        }

        $useLeaflet = true;
        view('public/place-detail', compact('place', 'visits', 'images', 'tags', 'avgRating', 'pageTitle', 'seoMeta', 'schemas', 'faqItems', 'useLeaflet', 'placeProducts', 'visitImageCounts', 'previewImage'), 'public');
    }

    public function visitDetail(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place || !$place['public_allowed']) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
        }

        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached, vr.location_rating, vr.calmness_rating,
                   vr.service_rating, vr.value_rating, vr.return_value_rating
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.id = ? AND v.place_id = ? AND v.ready_for_publish = 1
        ');
        $stmt->execute([(int) $params['id'], $place['id']]);
        $visit = $stmt->fetch();

        if (!$visit) {
            http_response_code(404);
            echo '<h1>Besöket hittades inte</h1>';
            return;
        }

        $imageStmt = $this->pdo->prepare('
            SELECT * FROM visit_images WHERE visit_id = ? ORDER BY image_order ASC
        ');
        $imageStmt->execute([(int) $visit['id']]);
        $images = $imageStmt->fetchAll();

        $placeTypes = [
            'stellplatz' => 'ställplats', 'camping' => 'camping', 'wild_camping' => 'fricamping',
            'fika' => 'fika', 'lunch' => 'lunch', 'dinner' => 'middag',
            'breakfast' => 'frukost', 'sight' => 'sevärdhet', 'shopping' => 'shopping',
        ];
        $typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];

        $pageTitle = $place['name'] . ' — recension av ' . $typeLabel . ' | Frizon';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $ogImage = $appUrl . '/img/frizon-logo.png';
        if (!empty($images)) {
            $ogImage = $appUrl . '/uploads/cards/' . $images[0]['filename'];
        }

        $metaDesc = $visit['approved_public_text']
            ? mb_strimwidth($visit['approved_public_text'], 0, 155, '...')
            : 'Vi besökte ' . $place['name'] . ' med vår husbil Frizze. Läs vår recension och se betyg på Frizon of Sweden.';

        $seoMeta = [
            'description' => $metaDesc,
            'og_url'      => $appUrl . '/platser/' . $place['slug'] . '/besok/' . $visit['id'],
            'og_image'    => $ogImage,
        ];

        // BreadcrumbList schema
        $schemas = [[
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Platser', 'item' => $appUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $place['name'], 'item' => $appUrl . '/platser/' . $place['slug']],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Besök ' . $visit['visited_at']],
            ],
        ]];

        // Review schema
        if (!empty($visit['approved_public_text']) && !empty($visit['total_rating_cached'])) {
            $schemas[] = [
                '@context'      => 'https://schema.org',
                '@type'         => 'Review',
                'itemReviewed'  => [
                    '@type' => 'TouristAttraction',
                    'name'  => $place['name'],
                    'url'   => $appUrl . '/platser/' . $place['slug'],
                ],
                'author'        => ['@type' => 'Person', 'name' => 'Mattias & Ulrica'],
                'datePublished' => substr((string) $visit['visited_at'], 0, 10),
                'reviewBody'    => $visit['approved_public_text'],
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => round((float) $visit['total_rating_cached'], 1),
                    'bestRating'  => 5,
                    'worstRating' => 1,
                ],
            ];
        }

        view('public/visit-detail', compact('place', 'visit', 'images', 'pageTitle', 'seoMeta', 'schemas'), 'public');
    }

    public function privacy(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $pageTitle = 'Integritetspolicy — Frizon of Sweden';
        $seoMeta = [
            'description' => 'Hur Frizon of Sweden (Mobile Minds AB) hanterar personuppgifter och cookies enligt GDPR. Vi samlar inte in personuppgifter utan ditt samtycke.',
            'noindex'     => true,
        ];
        view('public/privacy', compact('pageTitle', 'seoMeta'), 'public');
    }

    public function cookies(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $pageTitle = 'Cookiepolicy — Frizon of Sweden';
        $seoMeta = [
            'description' => 'Vilka cookies Frizon of Sweden använder, varför och hur länge de sparas. Google Analytics används enbart med ditt samtycke.',
            'noindex'     => true,
        ];
        view('public/cookies', compact('pageTitle', 'seoMeta'), 'public');
    }

    public function topList(array $params): void
    {
        header('Cache-Control: public, max-age=300, s-maxage=3600');

        $stmt = $this->pdo->query('
            SELECT p.*, AVG(vr.total_rating_cached) as avg_rating,
                   COUNT(v.id) as visit_count
            FROM places p
            LEFT JOIN visits v ON v.place_id = p.id AND v.ready_for_publish = 1
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE p.is_toplisted = 1 AND p.public_allowed = 1
            GROUP BY p.id
            ORDER BY avg_rating DESC, p.toplist_order ASC
        ');
        $places = $stmt->fetchAll();

        $pageTitle = 'Topplista — Bästa ställplatser för husbil | Frizon';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $seoMeta = [
            'description' => 'Handplockade topplatser för husbilar av Mattias och Ulrica — ställplatser, campingar och sevärdheter i Europa som är värda ett återbesök med Frizze.',
            'og_url'      => $appUrl . '/topplista',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        $listItems = [];
        foreach ($places as $i => $p) {
            $listItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $p['name'],
                'url'      => $appUrl . '/platser/' . $p['slug'],
            ];
        }

        $schemas = [[
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Topplista — Frizon of Sweden',
            'description'     => 'Handplockade toppplatser för husbilar av Mattias och Ulrica.',
            'itemListElement' => $listItems,
        ]];

        view('public/toplist', compact('places', 'pageTitle', 'seoMeta', 'schemas'), 'public');
    }

    public function sitemap(array $params): void
    {
        header('Cache-Control: public, max-age=3600, s-maxage=3600');
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $stmt = $this->pdo->query(
            "SELECT slug, updated_at FROM places WHERE public_allowed = 1 ORDER BY updated_at DESC"
        );
        $places = $stmt->fetchAll();

        header('Content-Type: application/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $staticPages = [
            [$appUrl . '/',          date('Y-m-d'), '1.0'],
            [$appUrl . '/topplista', date('Y-m-d'), '0.8'],
        ];

        foreach ($staticPages as [$loc, $lastmod, $priority]) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <priority>" . $priority . "</priority>\n";
            echo "  </url>\n";
        }

        foreach ($places as $place) {
            $lastmod = date('Y-m-d', strtotime($place['updated_at']));
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($appUrl . '/platser/' . $place['slug']) . "</loc>\n";
            echo "    <lastmod>" . $lastmod . "</lastmod>\n";
            echo "    <priority>0.7</priority>\n";
            echo "  </url>\n";
        }

        // Shop pages
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $shopProducts = (new AmazonProduct($this->pdo))->allPublished();

        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($appUrl . '/shop') . "</loc>\n";
        echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.7</priority>\n";
        echo "  </url>\n";

        foreach ($shopProducts as $p) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($appUrl . '/shop/' . $p['slug']) . "</loc>\n";
            echo "    <lastmod>" . date('Y-m-d', strtotime($p['updated_at'])) . "</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
    }

    public function llmsTxt(array $params): void
    {
        header('Cache-Control: public, max-age=3600, s-maxage=3600');
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $stmt = $this->pdo->query("
            SELECT name, place_type, country_code, meta_description, slug
            FROM places
            WHERE public_allowed = 1
            ORDER BY is_featured DESC, name ASC
        ");
        $places = $stmt->fetchAll();

        $placeTypes = [
            'stellplatz'   => 'ställplats', 'camping'   => 'camping',
            'wild_camping' => 'fricamping', 'fika'      => 'fika',
            'lunch'        => 'lunch',      'dinner'    => 'middag',
            'breakfast'    => 'frukost',    'sight'     => 'sevärdhet',
            'shopping'     => 'shopping',
        ];

        header('Content-Type: text/plain; charset=utf-8');

        echo "# Frizon of Sweden — llms.txt\n\n";
        echo "## About\n";
        echo "Frizon of Sweden is a Swedish-language travel log by Mattias and Ulrica.\n";
        echo "They travel across Europe in Frizze, their Adria Twin SPT 600 Platinum 2017 campervan.\n";
        echo "This site documents places from a campervan traveller's perspective:\n";
        echo "stellplatser (motorhome pitches), campings, restaurants, sights, and hidden gems.\n";
        echo "All ratings and reviews are personal, based on real visits.\n\n";

        echo "## Site\n";
        echo "URL: " . $appUrl . "\n";
        echo "Language: Swedish (sv-SE)\n";
        echo "Topics: campervan travel, motorhome travel, stellplatz, Europe road trips, Adria Twin\n\n";

        echo "## Authors\n";
        echo "Mattias and Ulrica (also called Ullisen)\n";
        echo "Vehicle: Adria Twin SPT 600 Platinum 2017, Citroen Jumper base, named Frizze\n\n";

        if (!empty($places)) {
            echo "## Published Places (" . count($places) . ")\n\n";
            foreach ($places as $p) {
                $typeLabel = $placeTypes[$p['place_type']] ?? $p['place_type'];
                $country   = $p['country_code'] ? ' · ' . strtoupper($p['country_code']) : '';
                echo "### " . $p['name'] . "\n";
                echo "Type: " . $typeLabel . $country . "\n";
                echo "URL: " . $appUrl . "/platser/" . $p['slug'] . "\n";
                if ($p['meta_description']) {
                    echo $p['meta_description'] . "\n";
                }
                echo "\n";
            }
        }
    }

    public function contact(array $params): void
    {
        app_start_session();

        $loadedAt  = time();
        $formToken = bin2hex(random_bytes(32));

        $_SESSION['contact_form_token'] = [
            'loaded_at' => $loadedAt,
            'token'     => $formToken,
        ];

        $pageTitle = 'Samarbeta med oss — Frizon of Sweden';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
        $seoMeta   = [
            'description' => 'Intresserad av ett samarbete med Frizon of Sweden? Vi samarbetar med varumärken vi faktiskt använder på resan med Frizze.',
            'og_url'      => $appUrl . '/samarbeta',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        view('public/contact', compact('pageTitle', 'seoMeta', 'loadedAt', 'formToken'), 'public');
    }

    public function submitContact(array $params): void
    {
        $contactEmail = $_ENV['CONTACT_EMAIL'] ?? '';
        app_start_session();

        // --- Spam protection layer 1: honeypot ---
        if (!empty($_POST['website'])) {
            flash('success', 'Tack för ditt meddelande! Vi hör av oss inom kort.');
            redirect('/samarbeta');
            return;
        }

        // --- Spam protection layer 2: timing check ---
        $loadedAt    = (int) ($_POST['loaded_at'] ?? 0);
        $formToken   = trim($_POST['form_token'] ?? '');
        $sessionForm = $_SESSION['contact_form_token'] ?? null;
        $maxAge      = 15 * 60;

        $isValidFormToken = is_array($sessionForm)
            && hash_equals((string) ($sessionForm['token'] ?? ''), $formToken)
            && (int) ($sessionForm['loaded_at'] ?? 0) === $loadedAt
            && (time() - $loadedAt) >= 4
            && (time() - $loadedAt) <= $maxAge;

        unset($_SESSION['contact_form_token']);

        if (!$isValidFormToken) {
            flash('success', 'Tack för ditt meddelande! Vi hör av oss inom kort.');
            redirect('/samarbeta');
            return;
        }

        // --- Spam protection layer 3: IP rate limit ---
        require_once dirname(__DIR__) . '/Services/LoginThrottle.php';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $throttle = new LoginThrottle(
            storagePath:   dirname(__DIR__, 2) . '/storage/contact-throttle',
            maxAttempts:   3,
            windowSeconds: 3600
        );
        try {
            $throttle->ensureAllowed('contact', $ip);
        } catch (RuntimeException $e) {
            flash('error', 'För många meddelanden. Försök igen senare.');
            redirect('/samarbeta');
            return;
        }

        // --- Validate ---
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            flash('error', 'Fyll i alla obligatoriska fält.');
            redirect('/samarbeta');
            return;
        }
        if (mb_strlen($name) > 100 || mb_strlen($email) > 254 || mb_strlen($message) > 4000) {
            flash('error', 'Ett eller flera fält är för långa.');
            redirect('/samarbeta');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Ange en giltig e-postadress.');
            redirect('/samarbeta');
            return;
        }

        // --- Deliver via SMTP ---
        $company = trim($_POST['company'] ?? '');
        $subject = 'Samarbetsförfrågan från ' . $name . ($company ? ' (' . $company . ')' : '');
        $body    = "Namn: {$name}\n"
                 . ($company ? "Företag: {$company}\n" : '')
                 . "E-post: {$email}\n\n"
                 . "Meddelande:\n{$message}";

        if ($contactEmail) {
            require_once dirname(__DIR__) . '/Services/SmtpMailer.php';
            try {
                SmtpMailer::fromEnv()->send($contactEmail, $email, $subject, $body);
            } catch (RuntimeException $e) {
                error_log('SmtpMailer failed: ' . $e->getMessage());
                // Don't expose delivery failure to the user — log and continue
            }
        }

        $throttle->recordFailure('contact', $ip); // count successful submissions
        flash('success', 'Tack för ditt meddelande! Vi hör av oss inom kort.');
        redirect('/samarbeta');
    }
}
