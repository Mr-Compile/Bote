<?php

declare(strict_types=1);

/**
 * Minimal .env loader for BoteBank.
 *
 * Loads environment variables from a .env file and exposes them via
 * getenv() and $_ENV. Falls back to already-populated environment variables
 * so that server-level configuration always wins.
 */

if (!function_exists('bote_load_env')) {
    /**
     * Parse a .env file and populate $_ENV / putenv().
     */
    function bote_load_env(string $path): void
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
            $value = trim($value, "\"'");

            if ($key === '') {
                continue;
            }

            // Server environment always takes precedence.
            $existing = getenv($key);
            if ($existing === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Read an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            if (array_key_exists($key, $_ENV)) {
                $value = $_ENV[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }
}

// Look for .env in project root first, then in this directory.
$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

if (is_file($envPath)) {
    bote_load_env($envPath);
} elseif (is_file(__DIR__ . DIRECTORY_SEPARATOR . '.env')) {
    bote_load_env(__DIR__ . DIRECTORY_SEPARATOR . '.env');
}
