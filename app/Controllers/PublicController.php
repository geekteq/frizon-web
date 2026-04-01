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
        // Public places with ratings
        $stmt = $this->pdo->query('
            SELECT p.*, AVG(vr.total_rating_cached) as avg_rating,
                   COUNT(v.id) as visit_count
            FROM places p
            LEFT JOIN visits v ON v.place_id = p.id AND v.ready_for_publish = 1
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE p.public_allowed = 1
            GROUP BY p.id
            ORDER BY p.is_featured DESC, p.updated_at DESC
        ');
        $places = $stmt->fetchAll();

        // Active filters
        $filterType = $_GET['type'] ?? null;
        $filterCountry = $_GET['country'] ?? null;

        if ($filterType) {
            $places = array_filter($places, fn($p) => $p['place_type'] === $filterType);
        }
        if ($filterCountry) {
            $places = array_filter($places, fn($p) => $p['country_code'] === $filterCountry);
        }
        $places = array_values($places);

        // Unique countries and types for filters
        $allPublic = $this->pdo->query('SELECT DISTINCT country_code FROM places WHERE public_allowed = 1 AND country_code IS NOT NULL ORDER BY country_code')->fetchAll(PDO::FETCH_COLUMN);
        $allTypes = $this->pdo->query('SELECT DISTINCT place_type FROM places WHERE public_allowed = 1 ORDER BY place_type')->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = 'Frizon of Sweden';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $seoMeta = [
            'description' => 'Platser vi besökt med Frizze, vår Adria Twin. Ställplatser, campingar, restauranger och sevärdheter — sett ur ett husbilsperspektiv.',
            'og_url'      => $appUrl . '/',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        $schemas = [[
            '@context'    => 'https://schema.org',
            '@type'       => 'WebSite',
            'name'        => 'Frizon of Sweden',
            'url'         => $appUrl,
            'description' => 'Resedagbok med Frizze — platser vi besökt med vår husbil i Europa.',
            'inLanguage'  => 'sv',
            'author'      => [
                '@type' => 'Person',
                'name'  => 'Mattias & Ulrica',
                'url'   => $appUrl,
            ],
        ]];

        view('public/homepage', compact('places', 'filterType', 'filterCountry', 'allPublic', 'allTypes', 'pageTitle', 'seoMeta', 'schemas'), 'public');
    }

    public function placeDetail(array $params): void
    {
        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place || !$place['public_allowed']) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
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

        // Tags
        $tagStmt = $this->pdo->prepare('SELECT tag FROM place_tags WHERE place_id = ?');
        $tagStmt->execute([$place['id']]);
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        // Avg rating
        $ratingStmt = $this->pdo->prepare('
            SELECT AVG(vr.total_rating_cached) as avg_rating
            FROM visits v
            JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
        ');
        $ratingStmt->execute([$place['id']]);
        $avgRating = $ratingStmt->fetchColumn();

        $pageTitle = $place['name'];
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        // og:image — first uploaded image or logo fallback
        $ogImage = $appUrl . '/img/frizon-logo.png';
        if (!empty($images)) {
            $ogImage = $appUrl . '/uploads/cards/' . $images[0]['filename'];
        }

        $metaDesc = $place['meta_description']
            ?? ($place['default_public_text'] ? mb_strimwidth($place['default_public_text'], 0, 155, '...') : null)
            ?? $place['name'] . ' — besökt av Mattias och Ulrica på Frizon of Sweden.';

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

        view('public/place-detail', compact('place', 'visits', 'images', 'tags', 'avgRating', 'pageTitle', 'seoMeta', 'schemas', 'faqItems'), 'public');
    }

    public function privacy(array $params): void
    {
        $pageTitle = 'Integritetspolicy — Frizon';
        view('public/privacy', compact('pageTitle'), 'public');
    }

    public function cookies(array $params): void
    {
        $pageTitle = 'Cookiepolicy — Frizon';
        view('public/cookies', compact('pageTitle'), 'public');
    }

    public function topList(array $params): void
    {
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

        $pageTitle = 'Topplista — Frizon';
        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');

        $seoMeta = [
            'description' => 'Våra bästa platser, handplockade av Mattias och Ulrica. Ställplatser, campingar och sevärdheter för husbilar i Europa.',
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

        echo '</urlset>';
    }

    public function llmsTxt(array $params): void
    {
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
}
