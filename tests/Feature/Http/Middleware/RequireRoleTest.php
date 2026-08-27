<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequireRole;
use PHPUnit\Framework\TestCase;

final class RequireRoleTest extends TestCase
{
    public function testRequireRoleIsCallable(): void
    {
        $this->assertTrue(is_callable([RequireRole::class, 'assert']));
    }
}
