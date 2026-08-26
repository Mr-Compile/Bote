<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public const TOKEN_KEY = 'csrf_token';
    public const INPUT_NAME = 'csrf_token';

    public static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function token(): string
    {
        self::ensureSessionStarted();

        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    public static function field(): string
    {
        $token = self::token();

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::INPUT_NAME,
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
        );
    }

    public static function validate(?string $token): void
    {
        self::ensureSessionStarted();

        if (empty($token)) {
            throw new \InvalidArgumentException('CSRF token missing');
        }

        if (!hash_equals($_SESSION[self::TOKEN_KEY] ?? '', $token)) {
            throw new \InvalidArgumentException('CSRF token invalid');
        }
    }

    public static function submittedToken(): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return null;
        }

        if (!empty($_POST[self::INPUT_NAME])) {
            return (string) $_POST[self::INPUT_NAME];
        }

        $header = 'HTTP_X_CSRF_TOKEN';
        if (!empty($_SERVER[$header])) {
            return (string) $_SERVER[$header];
        }

        $input = file_get_contents('php://input');
        if ($input === '' || $input === false) {
            return null;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($input, true);
            if (is_array($data) && isset($data[self::INPUT_NAME])) {
                return (string) $data[self::INPUT_NAME];
            }
        }

        return null;
    }

    public static function validateRequest(): void
    {
        self::validate(self::submittedToken());
    }

    public static function reset(): void
    {
        self::ensureSessionStarted();
        $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
    }
}
