<?php
/**
 * Loader environment variable tanpa dependency eksternal.
 */

if (!function_exists('load_env')) {
    function load_env(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Buang tanda kutip pembungkus
            $value = trim($value, "\"'");

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        $lower = strtolower((string) $value);

        return match ($lower) {
            'true' => true,
            'false' => false,
            'null', 'empty' => null,
            default => $value,
        };
    }
}

// Muat .env jika belum dimuat (dukung konfigurasi terpisah untuk testing)
if (!defined('ENV_LOADED')) {
    define('ENV_LOADED', true);
    load_env(dirname(__DIR__) . '/.env');

    // Fallback ke .env.example jika .env belum dibuat
    if (!is_file(dirname(__DIR__) . '/.env')) {
        load_env(dirname(__DIR__) . '/.env.example');
    }
}
