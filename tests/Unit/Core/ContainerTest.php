<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use App\Services\DropoffService;
use App\Services\PickupService;
use App\Services\PointsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeConnection;

final class ContainerTest extends TestCase
{
    public function test_container_wires_services_from_connection(): void
    {
        $container = new Container(new FakeConnection());

        self::assertInstanceOf(PickupService::class, $container->pickupService);
        self::assertInstanceOf(DropoffService::class, $container->dropoffService);
        self::assertInstanceOf(PointsService::class, $container->pointsService);
    }
}
