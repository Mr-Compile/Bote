<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pickup;

interface PickupRepositoryInterface
{
    public function findById(int $requestId): ?Pickup;

    public function save(Pickup $pickup): bool;

    public function assign(int $requestId, int $workerId): bool;

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
    ): int;
}
