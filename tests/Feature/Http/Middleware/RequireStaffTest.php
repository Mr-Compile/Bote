<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequireStaff;
use PHPUnit\Framework\TestCase;

final class RequireStaffTest extends TestCase
{
    public function testRequireStaffIsCallable(): void
    {
        $this->assertTrue(is_callable([RequireStaff::class, 'assert']));
    }
}
