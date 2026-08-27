<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Http\Controllers\Admin\DashboardController;
use PHPUnit\Framework\TestCase;

final class DashboardControllerTest extends TestCase
{
    public function testControllerExistsAndHasRenderMethod(): void
    {
        $this->assertTrue(method_exists(DashboardController::class, 'render'));
    }
}
