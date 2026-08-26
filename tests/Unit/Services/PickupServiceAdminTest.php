<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\PickupStatus;
use App\Enums\Unit;
use App\Models\Pickup;
use App\Models\RecyclableType;
use App\Services\PickupService;
use App\Services\PointsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeConnection;
use Tests\Support\FakePickupRepository;
use Tests\Support\FakePointsRepository;

final class PickupServiceAdminTest extends TestCase
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

    public function test_complete_as_admin_uses_stored_quantity_for_pcs(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Pcs, 0.01, 25, pointsPerUnit: 2.0);
        $this->pickups->save($pickup);

        $completed = $this->service->completeAsAdmin(
            requestId: $pickup->requestId,
            actualWeight: 0.01,
            actualQuantity: null,
            description: 'Admin completion',
        );

        self::assertSame(PickupStatus::Completed, $completed->status);
        self::assertSame(25, $completed->quantityPcs);
        self::assertSame(50, $completed->pointsEarned);
        self::assertSame($pickup->residentId, $this->points->lastUserId);
        self::assertSame('Admin completion', $this->points->lastDescription);
    }

    public function test_complete_as_admin_does_not_check_worker(): void
    {
        $pickup = $this->createScheduledPickup(Unit::Kg, 12.5);
        $this->pickups->save($pickup);

        $completed = $this->service->completeAsAdmin(
            requestId: $pickup->requestId,
            actualWeight: 10.0,
            actualQuantity: null,
            description: null,
        );

        self::assertSame(100, $completed->pointsEarned);
    }

    private function createScheduledPickup(Unit $unit, float $weight, ?int $quantity = null, ?float $pointsPerKg = 10.0, ?float $pointsPerUnit = null): Pickup
    {
        $type = new RecyclableType(
            typeId: 1,
            name: 'Material',
            unit: $unit,
            pointsPerKg: $unit === Unit::Kg ? $pointsPerKg : null,
            pointsPerUnit: $unit === Unit::Pcs ? $pointsPerUnit : null,
            image: null,
        );

        return Pickup::fromArray([
            'request_id' => 5,
            'resident_id' => 10,
            'type_id' => 1,
            'weight_kg' => $weight,
            'quantity_pcs' => $quantity,
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
