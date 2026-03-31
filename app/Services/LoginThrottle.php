<?php

declare(strict_types=1);

class LoginThrottle
{
    private string $storagePath;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(?string $storagePath = null, int $maxAttempts = 5, int $windowSeconds = 900)
    {
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage/login-throttle';
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0700, true);
        }
    }

    public function ensureAllowed(string $username, string $ipAddress): void
    {
        $attempts = $this->readAttempts($username, $ipAddress);
        if (count($attempts) < $this->maxAttempts) {
            return;
        }

        $retryAfter = $this->windowSeconds - (time() - $attempts[0]);
        $retryAfterMinutes = max(1, (int) ceil($retryAfter / 60));

        throw new RuntimeException('För många misslyckade inloggningar. Försök igen om ' . $retryAfterMinutes . ' minut(er).');
    }

    public function recordFailure(string $username, string $ipAddress): void
    {
        $attempts = $this->readAttempts($username, $ipAddress);
        $attempts[] = time();
        $this->writeAttempts($username, $ipAddress, $attempts);
    }

    public function clear(string $username, string $ipAddress): void
    {
        $path = $this->pathFor($username, $ipAddress);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function pathFor(string $username, string $ipAddress): string
    {
        $key = strtolower(trim($username)) . '|' . trim($ipAddress);
        return $this->storagePath . '/' . hash('sha256', $key) . '.json';
    }

    private function readAttempts(string $username, string $ipAddress): array
    {
        $path = $this->pathFor($username, $ipAddress);
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);
        $attempts = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
        $cutoff = time() - $this->windowSeconds;

        return array_values(array_filter($attempts, static fn ($ts) => is_int($ts) && $ts >= $cutoff));
    }

    private function writeAttempts(string $username, string $ipAddress, array $attempts): void
    {
        $path = $this->pathFor($username, $ipAddress);
        $cutoff = time() - $this->windowSeconds;
        $freshAttempts = array_values(array_filter($attempts, static fn ($ts) => is_int($ts) && $ts >= $cutoff));

        file_put_contents($path, json_encode(['attempts' => $freshAttempts], JSON_THROW_ON_ERROR), LOCK_EX);
    }
}
