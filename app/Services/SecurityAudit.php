<?php

declare(strict_types=1);

class SecurityAudit
{
    private static bool $disabled = false;

    public static function log(PDO $pdo, string $eventType, array $details = [], ?int $userId = null): void
    {
        if (self::$disabled) {
            return;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO security_audit_log (
                    user_id,
                    event_type,
                    request_path,
                    ip_address,
                    user_agent,
                    details_json
                ) VALUES (?, ?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $userId,
                $eventType,
                app_request_path(),
                self::truncate($_SERVER['REMOTE_ADDR'] ?? null, 45),
                self::truncate($_SERVER['HTTP_USER_AGENT'] ?? null, 500),
                json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable $e) {
            self::$disabled = true;
            error_log('Security audit logging failed for event ' . $eventType . ': ' . $e->getMessage());
        }
    }

    private static function truncate(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }
}
