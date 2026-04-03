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
    private string $associateId;
    private string $uploadDir;

    public function __construct(string $associateId, string $uploadDir)
    {
        $this->associateId = $associateId;
        $this->uploadDir   = rtrim($uploadDir, '/');
    }

    /**
     * Build an affiliate URL by injecting the associate tag into an Amazon URL.
     * Replaces any existing tag= parameter, or appends it.
     */
    public function buildAffiliateUrl(string $amazonUrl): string
    {
        $parsed = parse_url($amazonUrl);
        if (!$parsed) {
            return $amazonUrl;
        }

        parse_str($parsed['query'] ?? '', $params);
        $params['tag'] = $this->associateId;

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '')
              . ($parsed['path'] ?? '');

        return $base . '?' . http_build_query($params);
    }

    /**
     * Validate that a URL is on an Amazon domain.
     */
    public function isAmazonUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return (bool) preg_match('/(?:^|\.)amazon\.[a-z]{2,3}(?:\.[a-z]{2})?$/i', $host);
    }

    /**
     * Fetch og:image and og:description from an Amazon product page.
     * Returns ['image_url' => string|null, 'description' => string|null].
     */
    public function fetchProductMeta(string $amazonUrl): array
    {
        $ch = curl_init($amazonUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept-Language: sv-SE,sv;q=0.9',
                'Accept: text/html,application/xhtml+xml',
            ],
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
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
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);

        $imageData = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$imageData || $httpCode !== 200) {
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
        $webpData = $this->processToWebp($rawData);
        if (!$webpData) {
            return null;
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(8)) . '.webp';
        file_put_contents($this->uploadDir . '/' . $filename, $webpData);

        return $filename;
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
        imagedestroy($src);

        ob_start();
        imagewebp($dst, null, $quality);
        imagedestroy($dst);
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
}
