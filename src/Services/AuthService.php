<?php
declare(strict_types=1);

namespace App\Services;

class AuthService
{
    private static ?array $tokenData = null;

    public static function setToken(array $tokenData): void
    {
        self::$tokenData = $tokenData;
    }

    public static function getToken(): ?array
    {
        return self::$tokenData;
    }

    public static function getAccessToken(): ?string
    {
        return self::$tokenData['access_token'] ?? null;
    }

    public static function getRealmId(): ?string
    {
        return self::$tokenData['realm_id'] ?? null;
    }

    public static function isAuthenticated(): bool
    {
        $token = self::$tokenData;
        if (!$token || empty($token['access_token'])) {
            return false;
        }

        if (!empty($token['expires_at']) && time() >= (int) $token['expires_at']) {
            return false;
        }

        return true;
    }
}
