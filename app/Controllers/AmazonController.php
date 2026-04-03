<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Services/AmazonFetcher.php';
require_once dirname(__DIR__) . '/Models/AmazonProduct.php';

class AmazonController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo    = $pdo;
        $this->config = $config;
    }

    // -------------------------------------------------------------------------
    // Admin: list
    // -------------------------------------------------------------------------

    public function adminIndex(array $params): void
    {
        Auth::requireLogin();
        $model    = new AmazonProduct($this->pdo);
        $products = $model->all();
        view('amazon/index', compact('products'), 'app');
    }

    // -------------------------------------------------------------------------
    // Admin: create form
    // -------------------------------------------------------------------------

    public function adminCreate(array $params): void
    {
        Auth::requireLogin();
        $categories = (new AmazonProduct($this->pdo))->allCategories();
        view('amazon/create', compact('categories'), 'app');
    }

    // -------------------------------------------------------------------------
    // Admin: store (POST)
    // -------------------------------------------------------------------------

    public function adminStore(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo 'Ogiltig säkerhetstoken.';
            return;
        }

        $title     = trim($_POST['title'] ?? '');
        $amazonUrl = trim($_POST['amazon_url'] ?? '');

        if ($title === '' || $amazonUrl === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Titel och Amazon-URL krävs.'];
            header('Location: /adm/amazon-lista/ny');
            return;
        }

        $fetcher = $this->makeFetcher();

        if (!$fetcher->isAmazonUrl($amazonUrl)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'URL:en måste vara en Amazon-domän.'];
            header('Location: /adm/amazon-lista/ny');
            return;
        }

        $affiliateUrl      = $fetcher->buildAffiliateUrl($amazonUrl);
        $imagePath         = null;
        $amazonDescription = null;

        // Auto-fetch meta from Amazon
        $meta = $fetcher->fetchProductMeta($amazonUrl);

        if ($meta['image_url']) {
            $filename = $fetcher->downloadImage($meta['image_url']);
            if ($filename) {
                $imagePath = $filename;
            }
        }

        if ($meta['description']) {
            $amazonDescription = $this->ensureSwedish($meta['description']);
        }

        // Generate SEO fields via AI
        $seoData = $this->generateSeo([
            'title'               => $title,
            'amazon_description'  => $amazonDescription,
            'our_description'     => trim($_POST['our_description'] ?? ''),
            'category'            => trim($_POST['category'] ?? ''),
        ]);

        $model = new AmazonProduct($this->pdo);
        $id    = $model->create([
            'slug'               => AmazonProduct::generateSlug($title),
            'title'              => $title,
            'amazon_url'         => $amazonUrl,
            'affiliate_url'      => $affiliateUrl,
            'image_path'         => $imagePath,
            'amazon_description' => $amazonDescription,
            'our_description'    => trim($_POST['our_description'] ?? '') ?: null,
            'seo_title'          => $seoData['seo_title'] ?? null,
            'seo_description'    => $seoData['seo_description'] ?? null,
            'category'           => trim($_POST['category'] ?? '') ?: null,
            'sort_order'         => (int) ($_POST['sort_order'] ?? 0),
            'is_featured'        => isset($_POST['is_featured']) ? 1 : 0,
            'is_published'       => isset($_POST['is_published']) ? 1 : 0,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produkt skapad.'];
        header('Location: /adm/amazon-lista/' . $id . '/redigera');
    }

    // -------------------------------------------------------------------------
    // Admin: edit form
    // -------------------------------------------------------------------------

    public function adminEdit(array $params): void
    {
        Auth::requireLogin();
        $id      = (int) ($params['id'] ?? 0);
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if (!$product) {
            http_response_code(404);
            echo '<h1>Produkten hittades inte</h1>';
            return;
        }

        $categories = $model->allCategories();
        view('amazon/edit', compact('product', 'categories'), 'app');
    }

    // -------------------------------------------------------------------------
    // Admin: update (PUT via POST override)
    // -------------------------------------------------------------------------

    public function adminUpdate(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo 'Ogiltig säkerhetstoken.';
            return;
        }

        $id    = (int) ($params['id'] ?? 0);
        $model = new AmazonProduct($this->pdo);
        $existing = $model->findById($id);

        if (!$existing) {
            http_response_code(404);
            echo '<h1>Produkten hittades inte</h1>';
            return;
        }

        $title     = trim($_POST['title'] ?? '');
        $amazonUrl = trim($_POST['amazon_url'] ?? '');

        if ($title === '' || $amazonUrl === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Titel och Amazon-URL krävs.'];
            header('Location: /adm/amazon-lista/' . $id . '/redigera');
            return;
        }

        $fetcher      = $this->makeFetcher();
        $affiliateUrl = $fetcher->buildAffiliateUrl($amazonUrl);
        $imagePath    = $existing['image_path'];
        $amazonDesc   = $existing['amazon_description'];

        // Image priority: file upload > manual URL > re-fetch (if URL changed or image missing)
        if (!empty($_FILES['product_image']['name'])) {
            $uploaded = $this->handleImageUpload($_FILES['product_image']);
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        } elseif ($manualUrl = trim($_POST['image_url_manual'] ?? '')) {
            $downloaded = $fetcher->downloadImage($manualUrl);
            if ($downloaded) {
                $imagePath = $downloaded;
            }
        } elseif ($amazonUrl !== $existing['amazon_url'] || !$existing['image_path']) {
            if (!$fetcher->isAmazonUrl($amazonUrl)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'URL:en måste vara en Amazon-domän.'];
                header('Location: /adm/amazon-lista/' . $id . '/redigera');
                return;
            }

            $meta = $fetcher->fetchProductMeta($amazonUrl);

            if ($meta['image_url']) {
                $filename = $fetcher->downloadImage($meta['image_url']);
                if ($filename) {
                    $imagePath = $filename;
                }
            }

            if ($meta['description']) {
                $amazonDesc = $this->ensureSwedish($meta['description']);
            }
        }

        // Allow manual override of SEO fields; regenerate if empty
        $seoTitle = trim($_POST['seo_title'] ?? '');
        $seoDesc  = trim($_POST['seo_description'] ?? '');

        if ($seoTitle === '' || $seoDesc === '') {
            $seoData  = $this->generateSeo([
                'title'              => $title,
                'amazon_description' => $amazonDesc,
                'our_description'    => trim($_POST['our_description'] ?? ''),
                'category'           => trim($_POST['category'] ?? ''),
            ]);
            $seoTitle = $seoTitle ?: ($seoData['seo_title'] ?? '');
            $seoDesc  = $seoDesc  ?: ($seoData['seo_description'] ?? '');
        }

        $model->update($id, [
            'title'              => $title,
            'amazon_url'         => $amazonUrl,
            'affiliate_url'      => $affiliateUrl,
            'image_path'         => $imagePath,
            'amazon_description' => $amazonDesc,
            'our_description'    => trim($_POST['our_description'] ?? '') ?: null,
            'seo_title'          => $seoTitle ?: null,
            'seo_description'    => $seoDesc  ?: null,
            'category'           => trim($_POST['category'] ?? '') ?: null,
            'sort_order'         => (int) ($_POST['sort_order'] ?? 0),
            'is_featured'        => isset($_POST['is_featured']) ? 1 : 0,
            'is_published'       => isset($_POST['is_published']) ? 1 : 0,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produkt uppdaterad.'];
        header('Location: /adm/amazon-lista/' . $id . '/redigera');
    }

    // -------------------------------------------------------------------------
    // Admin: destroy (DELETE via POST)
    // -------------------------------------------------------------------------

    public function adminDestroy(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            return;
        }

        $id    = (int) ($params['id'] ?? 0);
        $model = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if ($product && $product['image_path']) {
            $imagePath = dirname(__DIR__, 2) . '/storage/uploads/amazon/' . $product['image_path'];
            if (is_file($imagePath)) {
                unlink($imagePath);
            }
        }

        $model->delete($id);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produkt borttagen.'];
        header('Location: /adm/amazon-lista');
    }

    // -------------------------------------------------------------------------
    // Admin: re-fetch image + description from Amazon (POST)
    // -------------------------------------------------------------------------

    public function adminRefetch(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            return;
        }

        $id      = (int) ($params['id'] ?? 0);
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if (!$product) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produkten hittades inte.'];
            header('Location: /adm/amazon-lista');
            return;
        }

        $fetcher = $this->makeFetcher();
        $meta    = $fetcher->fetchProductMeta($product['amazon_url']);
        $updates = [];

        if ($meta['image_url']) {
            $filename = $fetcher->downloadImage($meta['image_url']);
            if ($filename) {
                $updates['image_path'] = $filename;
            }
        }

        if ($meta['description']) {
            $updates['amazon_description'] = $this->ensureSwedish($meta['description']);
        }

        if ($updates) {
            $model->updatePartial($id, $updates);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bild och beskrivning hämtade från Amazon.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Amazon svarade inte — lägg till bild manuellt nedan.'];
        }

        header('Location: /adm/amazon-lista/' . $id . '/redigera');
    }

    // -------------------------------------------------------------------------
    // Admin: toggle publish (POST)
    // -------------------------------------------------------------------------

    public function adminTogglePublish(array $params): void
    {
        Auth::requireLogin();

        if (!CsrfService::verify()) {
            http_response_code(403);
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        (new AmazonProduct($this->pdo))->togglePublished($id);

        header('Location: /adm/amazon-lista');
    }

    // -------------------------------------------------------------------------
    // Admin: AI "brodera ut" product description
    // -------------------------------------------------------------------------

    public function generateDraft(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken.']);
            return;
        }

        $id      = (int) ($params['id'] ?? 0);
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findById($id);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Produkten hittades inte.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $context = [
            'title'              => $product['title'],
            'amazon_description' => $product['amazon_description'] ?? '',
            'current_text'       => $input['current_text'] ?? $product['our_description'] ?? '',
        ];

        try {
            $ai   = new AiService();
            $text = $ai->generateShopDescription($context);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        echo json_encode(['success' => true, 'text' => $text]);
    }

    // -------------------------------------------------------------------------
    // Admin: categories autocomplete API
    // -------------------------------------------------------------------------

    public function categoriesApi(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $categories = (new AmazonProduct($this->pdo))->allCategories();
        echo json_encode($categories);
    }

    // -------------------------------------------------------------------------
    // Public: shop listing
    // -------------------------------------------------------------------------

    public function shopIndex(array $params): void
    {
        $model          = new AmazonProduct($this->pdo);
        $filterCategory = $_GET['kategori'] ?? null;
        $search         = trim($_GET['s'] ?? '');

        $filters = ['is_published' => 1];
        if ($filterCategory) {
            $filters['category'] = $filterCategory;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }
        $products   = $model->all($filters);
        $categories = $model->publishedCategories();

        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
        $pageTitle = 'Shop — Frizon of Sweden';

        $seoMeta = [
            'description' => 'Produkter vi verkligen använder och rekommenderar för husbilsresor. Noggrant utvalda av Mattias och Ulrica på Frizon of Sweden.',
            'og_url'      => $appUrl . '/shop',
            'og_image'    => $appUrl . '/img/frizon-logo.png',
        ];

        $schemas = [[
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => 'Frizon Shop — rekommenderade produkter',
            'url'         => $appUrl . '/shop',
            'description' => 'Produkter vi rekommenderar för husbilsresor.',
            'inLanguage'  => 'sv',
        ]];

        view('public/shop', compact(
            'products', 'categories', 'filterCategory', 'search', 'pageTitle', 'seoMeta', 'schemas'
        ), 'public');
    }

    // -------------------------------------------------------------------------
    // Public: product detail
    // -------------------------------------------------------------------------

    public function shopProduct(array $params): void
    {
        $model   = new AmazonProduct($this->pdo);
        $product = $model->findBySlug($params['slug']);

        if (!$product || !$product['is_published']) {
            http_response_code(404);
            echo '<h1>Produkten hittades inte</h1>';
            return;
        }

        $appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
        $pageTitle = ($product['seo_title'] ?: $product['title']) . ' — Frizon';

        $metaDesc = $product['seo_description']
            ?: ($product['our_description'] ? mb_strimwidth($product['our_description'], 0, 155, '...') : null)
            ?: 'Rekommenderas av Mattias och Ulrica på Frizon of Sweden.';

        $ogImage = $product['image_path']
            ? $appUrl . '/uploads/amazon/' . $product['image_path']
            : $appUrl . '/img/frizon-logo.png';

        $seoMeta = [
            'description' => $metaDesc,
            'og_url'      => $appUrl . '/shop/' . $product['slug'],
            'og_image'    => $ogImage,
        ];

        $schemas = [[
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product['title'],
            'description' => $product['amazon_description'] ?? $product['our_description'] ?? '',
            'url'         => $appUrl . '/shop/' . $product['slug'],
            'image'       => $ogImage,
            'offers'      => [
                '@type'       => 'Offer',
                'url'         => $product['affiliate_url'],
                'seller'      => ['@type' => 'Organization', 'name' => 'Amazon'],
                'availability'=> 'https://schema.org/InStock',
            ],
        ]];

        view('public/shop-product', compact('product', 'pageTitle', 'seoMeta', 'schemas', 'ogImage'), 'public');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function handleImageUpload(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };

        if (!$ext) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/amazon';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            return null;
        }

        return $filename;
    }

    private function makeFetcher(): AmazonFetcher
    {
        $associateId = $_ENV['AMAZON_ASSOCIATE_ID'] ?? '';
        $uploadDir   = dirname(__DIR__, 2) . '/storage/uploads/amazon';
        return new AmazonFetcher($associateId, $uploadDir);
    }

    private function ensureSwedish(string $text): string
    {
        if ($this->looksNonSwedish($text)) {
            try {
                $ai = new AiService();
                return $ai->translateToSwedish($text);
            } catch (RuntimeException) {
                return $text;
            }
        }
        return $text;
    }

    private function looksNonSwedish(string $text): bool
    {
        $hasSwedishChars = preg_match('/[åäöÅÄÖ]/', $text);
        $hasSwedishWords = preg_match('/\b(och|med|för|till|från|är|det|vi|att)\b/iu', $text);
        return !$hasSwedishChars && !$hasSwedishWords;
    }

    private function generateSeo(array $productData): array
    {
        try {
            $ai = new AiService();
            return $ai->generateShopSeo($productData);
        } catch (RuntimeException) {
            return [
                'seo_title'       => mb_substr($productData['title'] . ' — Frizon rekommenderar', 0, 60),
                'seo_description' => mb_substr(
                    $productData['amazon_description'] ?? "Vi rekommenderar {$productData['title']} för husbilsresor.",
                    0, 155
                ),
            ];
        }
    }
}
