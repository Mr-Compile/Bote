<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Dropoff;

interface DropoffRepositoryInterface
{
    public function findById(int $dropoffId): ?Dropoff;

    public function save(Dropoff $dropoff): bool;

    public function create(
        int $residentId,
        int $pointId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
        int $pointsEarned,
    ): int;
}
