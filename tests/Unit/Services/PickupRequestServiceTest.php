<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\PickupStatus;
use App\Enums\Unit;
use App\Models\RecyclableType;
use App\Services\PickupRequestService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePickupRepository;
use Tests\Support\FakeRecyclableTypeRepository;

final class PickupRequestServiceTest extends TestCase
{
    private FakePickupRepository $pickups;
    private FakeRecyclableTypeRepository $types;
    private PickupRequestService $service;

    protected function setUp(): void
    {
        $this->pickups = new FakePickupRepository();
        $this->types = new FakeRecyclableTypeRepository();
        $this->service = new PickupRequestService(
            pickups: $this->pickups,
            types: $this->types,
        );
    }

    public function test_creates_kg_home_pickup(): void
    {
        $this->types->add($this->kgType());

        $pickup = $this->service->create(
            residentId: 5,
            typeId: 1,
            weightKg: 15.5,
            quantityPcs: null,
            preferredDate: '2025-11-10 09:00:00',
            pickupType: 'Home',
            pointId: null,
            latitude: 6.42,
            longitude: 124.78,
        );

        self::assertSame(15.5, $pickup->weightKg);
        self::assertNull($pickup->quantityPcs);
        self::assertSame(PickupStatus::Pending, $pickup->status);
        self::assertNull($pickup->pointId);
    }

    public function test_creates_pcs_collection_point_pickup(): void
    {
        $this->types->add($this->pcsType());

        $pickup = $this->service->create(
            residentId: 5,
            typeId: 2,
            weightKg: 0.0,
            quantityPcs: 35,
            preferredDate: '2025-11-10 09:00:00',
            pickupType: 'CollectionPoint',
            pointId: 10,
            latitude: 6.43,
            longitude: 124.79,
        );

        self::assertSame(0.01, $pickup->weightKg);
        self::assertSame(35, $pickup->quantityPcs);
        self::assertSame(10, $pickup->pointId);
    }

    public function test_rejects_kg_weight_below_minimum(): void
    {
        $this->types->add($this->kgType());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum weight requirement is 10 kilograms');

        $this->service->create(
            residentId: 5,
            typeId: 1,
            weightKg: 5.0,
            quantityPcs: null,
            preferredDate: '2025-11-10 09:00:00',
            pickupType: 'Home',
            pointId: null,
            latitude: 6.42,
            longitude: 124.78,
        );
    }

    public function test_rejects_pcs_quantity_below_minimum(): void
    {
        $this->types->add($this->pcsType());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum quantity requirement is 30 pieces');

        $this->service->create(
            residentId: 5,
            typeId: 2,
            weightKg: 0.0,
            quantityPcs: 20,
            preferredDate: '2025-11-10 09:00:00',
            pickupType: 'CollectionPoint',
            pointId: 10,
            latitude: 6.43,
            longitude: 124.79,
        );
    }

    public function test_assigns_pending_pickup_to_worker(): void
    {
        $this->types->add($this->kgType());

        $pickup = $this->service->create(
            residentId: 5,
            typeId: 1,
            weightKg: 15.0,
            quantityPcs: null,
            preferredDate: '2025-11-10 09:00:00',
            pickupType: 'Home',
            pointId: null,
            latitude: 6.42,
            longitude: 124.78,
        );

        $assigned = $this->service->assignToWorker($pickup->requestId, 7);

        self::assertSame(7, $assigned->assignedWorkerId);
        self::assertSame(PickupStatus::Scheduled, $assigned->status);
    }

    public function test_rejects_assigning_non_pending_pickup(): void
    {
        $this->types->add($this->kgType());

        $pickup = $this->service->create(
            residentId: 5,
            typeId: 1,
            weightKg: 15.0,
            quantityPcs: null,
            preferredDate: '2025-11-10 09:00:00',
            pickupType: 'Home',
            pointId: null,
            latitude: 6.42,
            longitude: 124.78,
        );

        $this->service->assignToWorker($pickup->requestId, 7);

        $this->expectException(\DomainException::class);
        $this->service->assignToWorker($pickup->requestId, 8);
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
