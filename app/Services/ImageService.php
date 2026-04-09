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

        [$width, $height] = $imageInfo;
        if (!$this->isWithinImageLimits($width, $height)) {
            return null;
        }

        // All variants stored as WebP for Lighthouse performance
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.webp';

        // Save original (keep source format for archival)
        $origExt = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $originalFilename = str_replace('.webp', '.' . $origExt, $filename);
        $originalDest = $this->basePath . '/originals/' . $originalFilename;
        if (!move_uploaded_file($tmpPath, $originalDest)) {
            return null;
        }

        // Auto-correct EXIF orientation for JPEG (fixes rotated phone photos)
        if ($mimeType === 'image/jpeg') {
            $this->correctExifOrientation($originalDest);
        }

        // Generate WebP variants for all sizes
        foreach (self::VARIANTS as $dir => [$maxW, $maxH]) {
            $this->resize($originalDest, $this->basePath . '/' . $dir . '/' . $filename, $maxW, $maxH, $mimeType);
        }

        return ['filename' => $filename, 'original_filename' => $originalFilename];
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

        // Always output WebP for optimal Lighthouse scores
        imagewebp($resized, $dest, 82);

        imagedestroy($img);
        imagedestroy($resized);
    }

    private function isWithinImageLimits(int $width, int $height): bool
    {
        if ($width < 1 || $height < 1) {
            return false;
        }

        $maxWidth = (int) ($this->config['upload_max_width'] ?? 8000);
        $maxHeight = (int) ($this->config['upload_max_height'] ?? 8000);
        $maxPixels = (int) ($this->config['upload_max_pixels'] ?? 40000000);

        if ($width > $maxWidth || $height > $maxHeight) {
            return false;
        }

        return ($width * $height) <= $maxPixels;
    }

    /**
     * Rotate an image by 90° left or right, regenerating all variants.
     */
    public function rotate(string $filename, string $direction): bool
    {
        $stem    = pathinfo($filename, PATHINFO_FILENAME);
        $matches = glob($this->basePath . '/originals/' . $stem . '.*') ?: [];
        if (empty($matches)) return false;

        $originalPath = $matches[0];
        $ext          = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
        $mimeType     = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => null,
        };
        if (!$mimeType) return false;

        $img = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($originalPath),
            'image/png'  => imagecreatefrompng($originalPath),
            'image/webp' => imagecreatefromwebp($originalPath),
            default      => null,
        };
        if (!$img) return false;

        // imagerotate() is counter-clockwise; right (CW) = 270°, left (CCW) = 90°
        $degrees = ($direction === 'right') ? 270 : 90;
        $rotated = imagerotate($img, $degrees, 0);
        imagedestroy($img);
        if (!$rotated) return false;

        match ($mimeType) {
            'image/jpeg' => imagejpeg($rotated, $originalPath, 90),
            'image/png'  => imagepng($rotated, $originalPath),
            'image/webp' => imagewebp($rotated, $originalPath, 82),
        };

        // Regenerate all WebP variants from rotated original
        foreach (self::VARIANTS as $dir => [$maxW, $maxH]) {
            $this->resize($originalPath, $this->basePath . '/' . $dir . '/' . $filename, $maxW, $maxH, $mimeType);
        }

        imagedestroy($rotated);
        return true;
    }

    /**
     * Correct EXIF orientation for JPEG files (fixes sideways phone photos).
     */
    private function correctExifOrientation(string $path): void
    {
        if (!function_exists('exif_read_data')) return;

        $exif        = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;
        if ($orientation === 1) return;

        $img = @imagecreatefromjpeg($path);
        if (!$img) return;

        $rotated = match ((int) $orientation) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, 270, 0), // 90° CW
            8 => imagerotate($img, 90, 0),  // 90° CCW
            default => null,
        };

        if ($rotated) {
            imagejpeg($rotated, $path, 90);
            imagedestroy($rotated);
        }
        imagedestroy($img);
    }

    public function delete(string $filename): void
    {
        foreach (['thumbnails', 'cards', 'detail'] as $dir) {
            $path = $this->basePath . '/' . $dir . '/' . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $originalPattern = $this->basePath . '/originals/' . pathinfo($filename, PATHINFO_FILENAME) . '.*';
        foreach (glob($originalPattern) ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
