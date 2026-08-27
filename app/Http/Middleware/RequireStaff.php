<?php

declare(strict_types=1);

namespace App\Http\Middleware;

final class RequireStaff
{
    public static function assert(): void
    {
        RequireRole::assert('worker', 'admin');
    }
}
