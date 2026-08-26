<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pickup;

interface PickupRepositoryInterface
{
    public function findById(int $requestId): ?Pickup;

    public function save(Pickup $pickup): bool;
}
