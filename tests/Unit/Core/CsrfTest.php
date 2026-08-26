<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
    }

    public function test_generates_a_token(): void
    {
        $token = Csrf::token();

        self::assertNotEmpty($token);
        self::assertSame(64, strlen($token));
        self::assertSame($token, $_SESSION[Csrf::TOKEN_KEY]);
    }

    public function test_field_contains_token(): void
    {
        $field = Csrf::field();
        $token = Csrf::token();

        self::assertStringContainsString($token, $field);
        self::assertStringContainsString('type="hidden"', $field);
        self::assertStringContainsString('name="csrf_token"', $field);
    }

    public function test_validates_matching_token(): void
    {
        $token = Csrf::token();

        Csrf::validate($token);

        // No exception means success.
        self::assertTrue(true);
    }

    public function test_rejects_missing_token(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSRF token missing');

        Csrf::validate(null);
    }

    public function test_rejects_invalid_token(): void
    {
        Csrf::token();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSRF token invalid');

        Csrf::validate('invalid-token');
    }

    public function test_reset_changes_token(): void
    {
        $first = Csrf::token();
        Csrf::reset();
        $second = Csrf::token();

        self::assertNotSame($first, $second);
    }

    public function test_validate_request_from_post(): void
    {
        $token = Csrf::token();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[Csrf::INPUT_NAME] = $token;

        Csrf::validateRequest();

        // No exception means success.
        self::assertTrue(true);
    }

    public function test_validate_request_rejects_missing_header(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_POST = [];

        Csrf::validateRequest();
    }
}
