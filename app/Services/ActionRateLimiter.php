<?php

declare(strict_types=1);

require_once __DIR__ . '/LoginThrottle.php';

class ActionRateLimiter
{
    private string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage/action-throttle';
    }

    public function consumeForUser(string $action, int $userId, int $maxAttempts, int $windowSeconds): void
    {
        $throttle = new LoginThrottle(
            storagePath: $this->storagePath,
            maxAttempts: $maxAttempts,
            windowSeconds: $windowSeconds
        );

        $scope = $this->scopeForUser($action, $userId);
        $throttle->ensureAllowed($scope, '*');
        $throttle->recordFailure($scope, '*');
    }

    private function scopeForUser(string $action, int $userId): string
    {
        return 'action:' . strtolower(trim($action)) . '|user:' . $userId;
    }
}
