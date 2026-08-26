<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DropoffStatus;
use App\Enums\Unit;
use App\Models\RecyclableType;
use App\Services\DropoffRequestService;
use App\Services\PointsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDropoffRepository;
use Tests\Support\FakeRecyclableTypeRepository;

final class DropoffRequestServiceTest extends TestCase
{
    private FakeDropoffRepository $dropoffs;
    private FakeRecyclableTypeRepository $types;
    private DropoffRequestService $service;

    protected function setUp(): void
    {
        $this->dropoffs = new FakeDropoffRepository();
        $this->types = new FakeRecyclableTypeRepository();
        $this->service = new DropoffRequestService(
            dropoffs: $this->dropoffs,
            types: $this->types,
            calculator: new PointsService(),
        );
    }

    public function test_creates_kg_dropoff_and_calculates_points(): void
    {
        $this->types->add($this->kgType());

        $dropoff = $this->service->create(
            residentId: 5,
            pointId: 10,
            typeId: 1,
            weightKg: 15.5,
            quantityPcs: null,
        );

        self::assertSame(15.5, $dropoff->weightKg);
        self::assertNull($dropoff->quantityPcs);
        self::assertSame(155, $dropoff->pointsEarned);
        self::assertSame(DropoffStatus::Pending, $dropoff->status);
    }

    public function test_creates_pcs_dropoff_and_calculates_points(): void
    {
        $this->types->add($this->pcsType());

        $dropoff = $this->service->create(
            residentId: 5,
            pointId: 10,
            typeId: 2,
            weightKg: 0.0,
            quantityPcs: 35,
        );

        self::assertSame(0.01, $dropoff->weightKg);
        self::assertSame(35, $dropoff->quantityPcs);
        self::assertSame(70, $dropoff->pointsEarned);
    }

    public function test_rejects_kg_dropoff_below_minimum(): void
    {
        $this->types->add($this->kgType());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum weight requirement is 10 kilograms');

        $this->service->create(
            residentId: 5,
            pointId: 10,
            typeId: 1,
            weightKg: 5.0,
            quantityPcs: null,
        );
    }

    public function test_rejects_pcs_dropoff_below_minimum(): void
    {
        $this->types->add($this->pcsType());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum quantity requirement is 30 pieces');

        $this->service->create(
            residentId: 5,
            pointId: 10,
            typeId: 2,
            weightKg: 0.0,
            quantityPcs: 20,
        );
    }

    private function kgType(): RecyclableType
    {
        return new RecyclableType(
            typeId: 1,
            name: 'Plastic',
            unit: Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );
    }

    private function pcsType(): RecyclableType
    {
        return new RecyclableType(
            typeId: 2,
            name: 'Bottles',
            unit: Unit::Pcs,
            pointsPerKg: null,
            pointsPerUnit: 2.0,
            image: null,
        );
    }
}
