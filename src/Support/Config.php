<?php
declare(strict_types=1);

namespace App\Support;

final class Config
{
    public static function getString(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $default;
        return trim((string) $value);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, $_ENV)) {
            return $default;
        }

        $value = strtolower(trim((string) $_ENV[$key]));
        if ($value === '') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}
