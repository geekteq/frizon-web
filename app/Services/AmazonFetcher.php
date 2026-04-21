<?php

declare(strict_types=1);

// TODO: Replace og:meta scraping with a proper Amazon API once access is approved.
// Two options to evaluate:
//   1. PA-API (Product Advertising API) — structured product data (title, images, price).
//      Requires: AWS account + Associate approval + 3 qualified sales in 180 days.
//      See: https://webservices.amazon.com/paapi5/documentation/
//   2. Amazon Creator API (Influencer/Creator program) — may offer simpler access.
//      Evaluate which fits best when applying.
// When migrating: inject API credentials into this class, replace fetchProductMeta()
// with an API call, keep downloadImage() and buildAffiliateUrl() as-is.

class AmazonFetcher
{
    private const MAX_DOWNLOAD_BYTES = 10485760;
    private const CARD_VARIANT_DIR = 'amazon-thumb';
    private const CARD_VARIANT_MAX_SIZE = 340;
    private const CARD_VARIANT_QUALITY = 68;
    private const DETAIL_VARIANT_DIR = 'amazon-detail';
    private const DETAIL_VARIANT_MAX_SIZE = 680;
    private const DETAIL_VARIANT_QUALITY = 74;
    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    private string $associateId;
    private string $uploadDir;

    public function __construct(string $associateId, string $uploadDir)
    {
        $this->associateId = $associateId;
        $this->uploadDir   = rtrim($uploadDir, '/');
    }

    /**
     * Build a canonical affiliate URL in the format Amazon requires:
     *   https://www.amazon.se/dp/{ASIN}/ref=nosim?tag={associateId}
     *
     * This format qualifies for the direct-link bonus per Amazon's program rules.
     * Falls back to injecting tag= into the original URL if no ASIN is found.
     */
    public function buildAffiliateUrl(string $amazonUrl): string
    {
        $parsed = parse_url($amazonUrl);
        $host = $parsed['host'] ?? 'www.amazon.se';
        parse_str($parsed['query'] ?? '', $params);
        unset($params['tag']);

        // Extract ASIN (10 uppercase alphanumeric chars after /dp/)
        if (preg_match('~/dp/([A-Z0-9]{10})~i', $amazonUrl, $m)) {
            $params['tag'] = $this->associateId;

            return 'https://' . $host . '/dp/' . strtoupper($m[1])
                 . '/ref=nosim?' . http_build_query($params);
        }

        // Fallback: no ASIN found — inject tag into original URL
        $params['tag'] = $this->associateId;

        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '')
             . ($parsed['path'] ?? '') . '?' . http_build_query($params);
    }

    /**
     * Validate that a URL is on an Amazon domain.
     */
    public function isAmazonUrl(string $url): bool
    {
        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        return (bool) preg_match('/(?:^|\.)amazon\.[a-z]{2,3}(?:\.[a-z]{2})?$/i', $host);
    }

    public function isAllowedImageUrl(string $url): bool
    {
        if (!str_starts_with(strtolower($url), 'https://')) {
            return false;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        if ($host === '') {
            return false;
        }

        if ($this->isAmazonUrl($url)) {
            return true;
        }

        return (bool) preg_match(
            '/(?:^|\.)('
            . 'media-amazon\.com'
            . '|images-amazon\.com'
            . '|ssl-images-amazon\.com'
            . ')$/i',
            $host
        );
    }

    /**
     * Fetch og:image and og:description from an Amazon product page.
     * Returns ['image_url' => string|null, 'description' => string|null].
     */
    public function fetchProductMeta(string $amazonUrl): array
    {
        if (!$this->isAmazonUrl($amazonUrl)) {
            return ['image_url' => null, 'description' => null];
        }

        $ch = curl_init($amazonUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept-Language: sv-SE,sv;q=0.9',
                'Accept: text/html,application/xhtml+xml',
            ],
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $responseContentType = strtolower(trim((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE)));
        curl_close($ch);

        if (
            !$html
            || $httpCode !== 200
            || !$this->isAmazonUrl($effectiveUrl ?: $amazonUrl)
            || ($responseContentType !== '' && !str_contains($responseContentType, 'text/html'))
        ) {
            return ['image_url' => null, 'description' => null];
        }

        return [
            'image_url'   => $this->extractOgTag($html, 'og:image'),
            'description' => $this->extractOgTag($html, 'og:description'),
        ];
    }

    /**
     * Download an image from a URL, process it to WebP, and save it.
     * Returns the saved filename, or null on failure.
     */
    public function downloadImage(string $imageUrl): ?string
    {
        if (!$this->isAllowedImageUrl($imageUrl)) {
            return null;
        }

        $imageData = '';
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION  => static function ($ch, string $chunk) use (&$imageData): int {
                $imageData .= $chunk;

                if (strlen($imageData) > self::MAX_DOWNLOAD_BYTES) {
                    return 0;
                }

                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = strtolower(trim((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE)));
        $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if (
            $ok === false
            || $httpCode !== 200
            || !$this->isAllowedImageUrl($effectiveUrl)
            || !$this->isAllowedDownloadedImageType($contentType)
        ) {
            return null;
        }

        return $this->saveImageData($imageData);
    }

    /**
     * Process raw image bytes (any format) → WebP 800px max, and save.
     * Use this for both downloaded images and uploaded files.
     * Returns the saved filename, or null on failure.
     */
    public function saveImageData(string $rawData): ?string
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $webpData = $this->processToWebp($rawData);
        if ($webpData) {
            $filename = bin2hex(random_bytes(8)) . '.webp';
            file_put_contents($this->uploadDir . '/' . $filename, $webpData);
            $this->ensureResponsiveVariants($filename);
            return $filename;
        }

        // Fallback: GD/WebP not available — save original format
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($rawData);
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

        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        file_put_contents($this->uploadDir . '/' . $filename, $rawData);
        $this->ensureResponsiveVariants($filename);
        return $filename;
    }

    public function ensureResponsiveVariants(string $filename): bool
    {
        $cardOk = $this->ensureCardVariant($filename);
        $detailOk = $this->ensureDetailVariant($filename);

        return $cardOk && $detailOk;
    }

    public function ensureCardVariant(string $filename): bool
    {
        return $this->ensureVariant(
            $filename,
            self::CARD_VARIANT_DIR,
            self::CARD_VARIANT_MAX_SIZE,
            self::CARD_VARIANT_QUALITY
        );
    }

    public function ensureDetailVariant(string $filename): bool
    {
        return $this->ensureVariant(
            $filename,
            self::DETAIL_VARIANT_DIR,
            self::DETAIL_VARIANT_MAX_SIZE,
            self::DETAIL_VARIANT_QUALITY
        );
    }

    private function ensureVariant(string $filename, string $variantDirectory, int $maxSize, int $quality): bool
    {
        $filename = basename($filename);
        $sourcePath = $this->uploadDir . '/' . $filename;
        if (!is_file($sourcePath)) {
            return false;
        }

        $rawData = file_get_contents($sourcePath);
        if ($rawData === false) {
            return false;
        }

        $webpData = $this->processToWebp($rawData, $maxSize, $quality);
        if (!$webpData) {
            return false;
        }

        $variantDir = dirname($this->uploadDir) . '/' . $variantDirectory;
        if (!is_dir($variantDir) && !mkdir($variantDir, 0755, true) && !is_dir($variantDir)) {
            return false;
        }

        return file_put_contents($variantDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp', $webpData) !== false;
    }

    /**
     * Resize image to max 800px on longest side and convert to WebP (quality 82).
     * Returns WebP bytes, or null if GD unavailable or source unreadable.
     */
    private function processToWebp(string $rawData, int $maxSize = 800, int $quality = 82): ?string
    {
        if (!function_exists('imagewebp')) {
            // GD WebP not available — save original as-is with original extension
            return null;
        }

        $src = @imagecreatefromstring($rawData);
        if (!$src) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        if ($srcW > $maxSize || $srcH > $maxSize) {
            if ($srcW >= $srcH) {
                $newW = $maxSize;
                $newH = (int) round($srcH * $maxSize / $srcW);
            } else {
                $newH = $maxSize;
                $newW = (int) round($srcW * $maxSize / $srcH);
            }
        } else {
            $newW = $srcW;
            $newH = $srcH;
        }

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefilledrectangle($dst, 0, 0, $newW, $newH,
            imagecolorallocatealpha($dst, 255, 255, 255, 127));

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        ob_start();
        imagewebp($dst, null, $quality);
        $out = ob_get_clean();

        return $out ?: null;
    }

    private function extractOgTag(string $html, string $property): ?string
    {
        if (preg_match(
            '/<meta[^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\'](.*?)["\'][^>]*>/si',
            $html,
            $m
        )) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: null;
        }

        // Also try reversed attribute order: content first, then property
        if (preg_match(
            '/<meta[^>]+content=["\'](.*?)["\'][^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]*>/si',
            $html,
            $m
        )) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: null;
        }

        return null;
    }

    private function isAllowedDownloadedImageType(string $contentType): bool
    {
        if ($contentType === '') {
            return false;
        }

        foreach (self::ALLOWED_IMAGE_MIME_TYPES as $allowedType) {
            if (str_starts_with($contentType, $allowedType)) {
                return true;
            }
        }

        return false;
    }
}
