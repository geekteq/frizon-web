<?php

declare(strict_types=1);

class AuthMiddleware
{
    public static function handle(): void
    {
        Auth::requireLogin();
    }
}
