<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Pickup;
use App\Repositories\PickupRepositoryInterface;

final class FakePickupRepository implements PickupRepositoryInterface
{
    /** @var array<int, Pickup> */
    private array $rows = [];
    private int $nextId = 1;

    public function findById(int $requestId): ?Pickup
    {
        return $this->rows[$requestId] ?? null;
    }

    public function save(Pickup $pickup): bool
    {
        $this->rows[$pickup->requestId] = $pickup;
        return true;
    }

    public function assign(int $requestId, int $workerId): bool
    {
        $pickup = $this->findById($requestId);

        if ($pickup === null) {
            return false;
        }

        $this->rows[$requestId] = $pickup->assignToWorker($workerId);
        return true;
    }

    public function create(
        int $residentId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
        string $preferredDate,
        string $pickupType,
        ?float $latitude,
        ?float $longitude,
        ?int $pointId,
    ): int {
        $id = $this->nextId++;
        $this->rows[$id] = Pickup::fromArray([
            'request_id' => $id,
            'resident_id' => $residentId,
            'type_id' => $typeId,
            'weight_kg' => $weightKg,
            'quantity_pcs' => $quantityPcs,
            'preferred_date' => $preferredDate,
            'status' => 'Pending',
            'assigned_worker_id' => null,
            'pickup_type' => $pickupType,
            'point_id' => $pointId,
            'actual_weight' => null,
            'points_earned' => null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'created_at' => '2025-11-01 09:00:00',
            'updated_at' => '2025-11-01 09:00:00',
        ], $this->typeFor($typeId));

        return $id;
    }

    private function typeFor(int $typeId): \App\Models\RecyclableType
    {
        return new \App\Models\RecyclableType(
            typeId: $typeId,
            name: 'Material',
            unit: \App\Enums\Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );
    }
}
