<?php

declare(strict_types=1);

/**
 * Instagram Graph API — content publishing for frizon.org.
 *
 * Setup:
 *   1. Create a Meta Business app with "Facebook Login" and permissions:
 *      instagram_business_basic, instagram_business_content_publish,
 *      pages_read_engagement, pages_show_list
 *   2. Generate a short-lived User Access Token in Graph API Explorer
 *   3. Exchange for long-lived token (valid 60 days):
 *      GET https://graph.facebook.com/v21.0/oauth/access_token
 *          ?grant_type=fb_exchange_token
 *          &client_id={INSTAGRAM_APP_ID}
 *          &client_secret={INSTAGRAM_APP_SECRET}
 *          &fb_exchange_token={short_lived_token}
 *   4. Get Instagram User ID:
 *      GET https://graph.facebook.com/v21.0/me/accounts?access_token={token}
 *      Then: GET https://graph.facebook.com/v21.0/{page_id}?fields=instagram_business_account&access_token={token}
 *   5. Put INSTAGRAM_USER_ID and INSTAGRAM_ACCESS_TOKEN in .env
 *
 * Images: Instagram requires JPEG. We auto-convert WebP → JPEG on publish.
 *         Converted files are cached in storage/uploads/instagram/.
 */
class InstagramService
{
    private const GRAPH_API     = 'https://graph.instagram.com/v21.0';
    private const MAX_CAROUSEL  = 10;
    private const MAX_CAPTION   = 2200;
    private const JPEG_QUALITY  = 90;

    private string $userId;
    private string $accessToken;
    private string $appUrl;
    private string $uploadPath;

    public function __construct(array $config)
    {
        $this->userId      = $config['instagram']['user_id'] ?? '';
        $this->accessToken = $config['instagram']['access_token'] ?? '';
        $this->appUrl      = rtrim($config['url'], '/');
        $this->uploadPath  = dirname(__DIR__, 2) . '/storage/uploads';
    }

    public function isConfigured(): bool
    {
        return $this->userId !== '' && $this->accessToken !== '';
    }

    /**
     * Build preview data for the confirmation modal (no API calls).
     */
    public function buildPreview(array $visit, array $place, array $images): array
    {
        $images = array_slice($images, 0, self::MAX_CAROUSEL);
        return [
            'caption'   => $this->buildCaption($visit, $place),
            'images'    => array_map(fn($img) => [
                'url'      => '/uploads/cards/' . $img['filename'],
                'filename' => $img['filename'],
            ], $images),
            'count'     => count($images),
            'place_url' => $this->appUrl . '/platser/' . $place['slug'],
        ];
    }

    /**
     * Publish to Instagram. Returns the published media ID.
     *
     * @param string[] $imageFilenames WebP filenames from visit_images
     * @throws RuntimeException on API error or image conversion failure
     */
    public function publish(array $imageFilenames, string $caption): string
    {
        if (empty($imageFilenames)) {
            throw new RuntimeException('Inga bilder att publicera.');
        }

        $caption = mb_substr(trim($caption), 0, self::MAX_CAPTION);
        $files   = array_slice($imageFilenames, 0, self::MAX_CAROUSEL);

        if (count($files) === 1) {
            $containerId = $this->createSingleContainer(
                $this->getPublicJpegUrl($files[0]),
                $caption
            );
        } else {
            $childIds    = array_map(fn($f) => $this->createItemContainer($this->getPublicJpegUrl($f)), $files);
            $containerId = $this->createCarouselContainer($childIds, $caption);
        }

        return $this->publishContainer($containerId);
    }

    public function buildCaption(array $visit, array $place): string
    {
        $parts = [$place['name'], ''];

        // Description: raw_note first, fall back to place description
        $desc = trim($visit['raw_note'] ?? '');
        if ($desc === '') {
            $desc = trim($place['default_public_text'] ?? '');
        }
        if ($desc !== '') {
            if (mb_strlen($desc) > 280) {
                $desc = mb_substr($desc, 0, 277) . '...';
            }
            $parts[] = $desc;
            $parts[] = '';
        }

        // Plus notes if short
        $plus = trim($visit['plus_notes'] ?? '');
        if ($plus !== '' && mb_strlen($plus) <= 120) {
            $parts[] = '+ ' . $plus;
            $parts[] = '';
        }

        // Date and location
        $meta = array_filter([
            $this->formatSwedishDate($visit['visited_at'] ?? ''),
            trim($place['address_text'] ?? ''),
        ]);
        if ($meta) {
            $parts[] = implode(' — ', $meta);
            $parts[] = '';
        }

        $parts[] = $this->appUrl . '/platser/' . $place['slug'];
        $parts[] = '';
        $parts[] = '#husbil #husbilar #campinglivet #vanlife #plåtis #frizze #frizon #camping #äventyr #sverige';

        return implode("\n", $parts);
    }

    private function formatSwedishDate(string $date): string
    {
        if (!$date) return '';
        $ts = strtotime($date);
        if ($ts === false) return $date;
        $months = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
        return date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }

    private function getPublicJpegUrl(string $webpFilename): string
    {
        $this->ensureJpeg($webpFilename);
        $stem = pathinfo($webpFilename, PATHINFO_FILENAME);
        return $this->appUrl . '/ig/' . $stem . '.jpg';
    }

    /**
     * Convert a WebP detail image to JPEG and cache it under public/ig/.
     * Falls back to original file if detail variant is missing.
     */
    private function ensureJpeg(string $webpFilename): void
    {
        $dir  = dirname(__DIR__, 2) . '/public/ig';
        $stem = pathinfo($webpFilename, PATHINFO_FILENAME);
        $dest = $dir . '/' . $stem . '.jpg';

        if (file_exists($dest)) return;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Try detail variant first (1200×900 WebP), then fall back to original
        $src = $this->uploadPath . '/detail/' . $webpFilename;
        if (!file_exists($src)) {
            $matches = glob($this->uploadPath . '/originals/' . $stem . '.*') ?: [];
            $src = $matches[0] ?? '';
        }

        if (!$src || !file_exists($src)) {
            throw new RuntimeException('Källbild saknas för: ' . $webpFilename);
        }

        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $img = match ($ext) {
            'webp'        => imagecreatefromwebp($src),
            'jpg', 'jpeg' => imagecreatefromjpeg($src),
            'png'         => imagecreatefrompng($src),
            default       => null,
        };

        if (!$img) {
            throw new RuntimeException('Kunde inte läsa bild: ' . $webpFilename);
        }

        if (!imagejpeg($img, $dest, self::JPEG_QUALITY)) {
            imagedestroy($img);
            throw new RuntimeException('Kunde inte skriva JPEG: ' . $dest);
        }
        imagedestroy($img);
    }

    private function createItemContainer(string $imageUrl): string
    {
        $data = $this->apiPost("/{$this->userId}/media", [
            'image_url'        => $imageUrl,
            'is_carousel_item' => 'true',
        ]);
        return $data['id'];
    }

    private function createCarouselContainer(array $childIds, string $caption): string
    {
        $data = $this->apiPost("/{$this->userId}/media", [
            'media_type' => 'CAROUSEL',
            'children'   => implode(',', $childIds),
            'caption'    => $caption,
        ]);
        return $data['id'];
    }

    private function createSingleContainer(string $imageUrl, string $caption): string
    {
        $data = $this->apiPost("/{$this->userId}/media", [
            'image_url' => $imageUrl,
            'caption'   => $caption,
        ]);
        return $data['id'];
    }

    private function publishContainer(string $containerId): string
    {
        $data = $this->apiPost("/{$this->userId}/media_publish", [
            'creation_id' => $containerId,
        ]);
        return $data['id'];
    }

    /** @throws RuntimeException */
    private function apiPost(string $endpoint, array $params): array
    {
        $params['access_token'] = $this->accessToken;

        $ch = curl_init(self::GRAPH_API . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Nätverksfel vid Instagram API-anrop.');
        }

        $json = json_decode((string) $body, true);

        if ($status >= 400 || isset($json['error'])) {
            $msg = $json['error']['message'] ?? $body;
            throw new RuntimeException('Instagram API fel: ' . $msg);
        }

        return (array) $json;
    }
}
