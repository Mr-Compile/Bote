<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\Unit;
use App\Models\RecyclableType;
use App\Services\PointsService;
use PHPUnit\Framework\TestCase;

final class PointsServiceTest extends TestCase
{
    private PointsService $service;

    protected function setUp(): void
    {
        $this->service = new PointsService();
    }

    public function test_calculate_kg_material_uses_weight(): void
    {
        $type = new RecyclableType(
            typeId: 1,
            name: 'Plastic',
            unit: Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );

        $points = $this->service->calculate($type, 5.5, null);

        self::assertSame(55, $points);
    }

    public function test_calculate_pcs_material_uses_quantity(): void
    {
        $type = new RecyclableType(
            typeId: 2,
            name: 'Glass Bottles',
            unit: Unit::Pcs,
            pointsPerKg: null,
            pointsPerUnit: 2.0,
            image: null,
        );

        $points = $this->service->calculate($type, 0.01, 30);

        self::assertSame(60, $points);
    }

    public function test_calculate_rejects_negative_weight_for_kg(): void
    {
        $type = new RecyclableType(
            typeId: 1,
            name: 'Plastic',
            unit: Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculate($type, -1.0, null);
    }

    public function test_calculate_rejects_missing_quantity_for_pcs(): void
    {
        $type = new RecyclableType(
            typeId: 2,
            name: 'Glass Bottles',
            unit: Unit::Pcs,
            pointsPerKg: null,
            pointsPerUnit: 2.0,
            image: null,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculate($type, 0.01, null);
    }
}
