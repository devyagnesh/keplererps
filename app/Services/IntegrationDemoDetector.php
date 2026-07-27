<?php

namespace App\Services;

/**
 * Detects seeded demo integration credentials (local testing without outbound HTTP).
 */
class IntegrationDemoDetector
{
    public static function isDemoCredential(?string $value): bool
    {
        return $value !== null && $value !== '' && str_starts_with($value, 'DEMO_');
    }

    public static function isDemoUrl(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        return str_contains($url, 'demo-') || str_contains($url, '.keplererp.local');
    }
}
