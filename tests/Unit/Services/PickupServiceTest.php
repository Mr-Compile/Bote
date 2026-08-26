<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\PickupStatus;
use App\Enums\Unit;
use App\Models\Pickup;
use App\Models\RecyclableType;
use App\Repositories\PickupRepositoryInterface;
use App\Repositories\PointsRepositoryInterface;
use App\Services\PickupService;
use App\Services\PointsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeConnection;
use Tests\Support\FakePickupRepository;
use Tests\Support\FakePointsRepository;

final class PickupServiceTest extends TestCase
{
    private FakeConnection $db;
    private FakePickupRepository $pickups;
    private FakePointsRepository $points;
    private PickupService $service;

    protected function setUp(): void
    {
        $this->db = new FakeConnection();
        $this->pickups = new FakePickupRepository();
        $this->points = new FakePointsRepository();
        $this->service = new PickupService(
            db: $this->db,
            pickups: $this->pickups,
            points: $this->points,
            calculator: new PointsService(),
        );
    }

    public function test_complete_kg_pickup_awards_points_and_updates_status(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Kg, 12.5);
        $this->pickups->save($pickup);

        $completed = $this->service->complete(
            requestId: $pickup->requestId,
            workerId: 3,
            actualWeight: 10.0,
            actualQuantity: null,
            description: 'Pickup completed - Plastic',
        );

        self::assertSame(PickupStatus::Completed, $completed->status);
        self::assertSame(10.0, $completed->actualWeight);
        self::assertSame(100, $completed->pointsEarned);

        self::assertSame(100, $this->points->lastAmount);
        self::assertSame($pickup->residentId, $this->points->lastUserId);
        self::assertSame($pickup->requestId, $this->points->lastRequestId);
        self::assertSame('Pickup completed - Plastic', $this->points->lastDescription);
    }

    public function test_complete_pcs_pickup_uses_quantity(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Pcs, 30, pointsPerUnit: 2.0);
        $this->pickups->save($pickup);

        $completed = $this->service->complete(
            requestId: $pickup->requestId,
            workerId: 3,
            actualWeight: 0.01,
            actualQuantity: 25,
            description: null,
        );

        self::assertSame(25, $completed->quantityPcs);
        self::assertSame(50, $completed->pointsEarned);
    }

    public function test_complete_throws_when_worker_not_assigned(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Kg, 12.5);
        $this->pickups->save($pickup);

        $this->expectException(\DomainException::class);
        $this->service->complete(
            requestId: $pickup->requestId,
            workerId: 99,
            actualWeight: 10.0,
            actualQuantity: null,
            description: null,
        );
    }

    public function test_complete_throws_when_already_completed(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Kg, 12.5);
        $pickup = $pickup->completed(actualWeight: 10.0, quantityPcs: null, pointsEarned: 100);
        $this->pickups->save($pickup);

        $this->expectException(\DomainException::class);
        $this->service->complete(
            requestId: $pickup->requestId,
            workerId: 3,
            actualWeight: 10.0,
            actualQuantity: null,
            description: null,
        );
    }

    public function test_complete_throws_when_missing_quantity_for_pcs(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Pcs, 0.01, pointsPerUnit: 2.0, quantity: null);
        $this->pickups->save($pickup);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->complete(
            requestId: $pickup->requestId,
            workerId: 3,
            actualWeight: 0.01,
            actualQuantity: null,
            description: null,
        );
    }

    private function createScheduledPickup(Unit $unit, float $weight, ?float $pointsPerKg = 10.0, ?float $pointsPerUnit = null, ?int $quantity = null): Pickup
    {
        $type = new RecyclableType(
            typeId: 1,
            name: 'Material',
            unit: $unit,
            pointsPerKg: $unit === Unit::Kg ? $pointsPerKg : null,
            pointsPerUnit: $unit === Unit::Pcs ? $pointsPerUnit : null,
            image: null,
        );

        $quantityPcs = $quantity ?? ($unit === Unit::Pcs ? (int) $weight : null);

        return Pickup::fromArray([
            'request_id' => 5,
            'resident_id' => 10,
            'type_id' => 1,
            'weight_kg' => $weight,
            'quantity_pcs' => $quantityPcs,
            'preferred_date' => '2025-11-05 10:00:00',
            'status' => 'Scheduled',
            'assigned_worker_id' => 3,
            'pickup_type' => 'Home',
            'point_id' => null,
            'actual_weight' => null,
            'points_earned' => null,
            'latitude' => 6.42,
            'longitude' => 124.78,
            'created_at' => '2025-11-01 09:00:00',
            'updated_at' => '2025-11-01 09:00:00',
        ], $type);
    }
}
