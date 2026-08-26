<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PickupStatus;
use App\Enums\Unit;
use App\Models\Pickup;
use App\Models\RecyclableType;
use PHPUnit\Framework\TestCase;

final class PickupTest extends TestCase
{
    public function test_from_array_creates_pickup(): void
    {
        $row = [
            'request_id' => 5,
            'resident_id' => 10,
            'type_id' => 1,
            'weight_kg' => 12.5,
            'quantity_pcs' => null,
            'preferred_date' => '2025-11-05 10:00:00',
            'status' => 'Pending',
            'assigned_worker_id' => null,
            'pickup_type' => 'Home',
            'point_id' => null,
            'actual_weight' => null,
            'points_earned' => null,
            'latitude' => 6.42,
            'longitude' => 124.78,
            'created_at' => '2025-11-01 09:00:00',
            'updated_at' => '2025-11-01 09:00:00',
        ];

        $type = new RecyclableType(
            typeId: 1,
            name: 'Plastic',
            unit: Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );

        $pickup = Pickup::fromArray($row, $type);

        self::assertSame(5, $pickup->requestId);
        self::assertSame(PickupStatus::Pending, $pickup->status);
        self::assertSame($type, $pickup->recyclableType);
        self::assertNull($pickup->actualWeight);
    }

    public function test_completed_returns_new_pickup_with_points(): void
    {
        $row = [
            'request_id' => 5,
            'resident_id' => 10,
            'type_id' => 1,
            'weight_kg' => 12.5,
            'quantity_pcs' => null,
            'preferred_date' => '2025-11-05 10:00:00',
            'status' => 'Pending',
            'assigned_worker_id' => 3,
            'pickup_type' => 'Home',
            'point_id' => null,
            'actual_weight' => null,
            'points_earned' => null,
            'latitude' => 6.42,
            'longitude' => 124.78,
            'created_at' => '2025-11-01 09:00:00',
            'updated_at' => '2025-11-01 09:00:00',
        ];

        $type = new RecyclableType(
            typeId: 1,
            name: 'Plastic',
            unit: Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );

        $pickup = Pickup::fromArray($row, $type)
            ->completed(actualWeight: 10.0, quantityPcs: null, pointsEarned: 100);

        self::assertSame(PickupStatus::Completed, $pickup->status);
        self::assertSame(10.0, $pickup->actualWeight);
        self::assertSame(100, $pickup->pointsEarned);
    }

    public function test_completed_throws_when_already_completed(): void
    {
        $row = [
            'request_id' => 5,
            'resident_id' => 10,
            'type_id' => 1,
            'weight_kg' => 12.5,
            'quantity_pcs' => null,
            'preferred_date' => '2025-11-05 10:00:00',
            'status' => 'Completed',
            'assigned_worker_id' => 3,
            'pickup_type' => 'Home',
            'point_id' => null,
            'actual_weight' => 10.0,
            'points_earned' => 100,
            'latitude' => 6.42,
            'longitude' => 124.78,
            'created_at' => '2025-11-01 09:00:00',
            'updated_at' => '2025-11-01 09:00:00',
        ];

        $type = new RecyclableType(
            typeId: 1,
            name: 'Plastic',
            unit: Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );

        $pickup = Pickup::fromArray($row, $type);

        $this->expectException(\DomainException::class);
        $pickup->completed(actualWeight: 5.0, quantityPcs: null, pointsEarned: 50);
    }
}
