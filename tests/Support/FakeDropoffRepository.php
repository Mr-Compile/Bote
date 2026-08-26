<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Dropoff;
use App\Models\RecyclableType;
use App\Repositories\DropoffRepositoryInterface;

final class FakeDropoffRepository implements DropoffRepositoryInterface
{
    /** @var array<int, Dropoff> */
    private array $rows = [];
    private int $nextId = 1;

    public function findById(int $dropoffId): ?Dropoff
    {
        return $this->rows[$dropoffId] ?? null;
    }

    public function save(Dropoff $dropoff): bool
    {
        $this->rows[$dropoff->dropoffId] = $dropoff;
        return true;
    }

    public function create(
        int $residentId,
        int $pointId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
        int $pointsEarned,
    ): int {
        $id = $this->nextId++;
        $this->rows[$id] = Dropoff::fromArray([
            'dropoff_id' => $id,
            'resident_id' => $residentId,
            'point_id' => $pointId,
            'type_id' => $typeId,
            'weight_kg' => $weightKg,
            'quantity_pcs' => $quantityPcs,
            'points_earned' => $pointsEarned,
            'status' => 'Pending',
            'verified_by' => null,
            'verification_notes' => null,
            'verified_at' => null,
            'created_at' => '2025-11-01 09:00:00',
        ], $this->typeFor($typeId));

        return $id;
    }

    private function typeFor(int $typeId): RecyclableType
    {
        return new RecyclableType(
            typeId: $typeId,
            name: 'Material',
            unit: \App\Enums\Unit::Kg,
            pointsPerKg: 10.0,
            pointsPerUnit: null,
            image: null,
        );
    }
}
