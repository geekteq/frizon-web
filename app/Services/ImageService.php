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
