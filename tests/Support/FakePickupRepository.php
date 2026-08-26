<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Pickup;
use App\Repositories\PickupRepositoryInterface;

final class FakePickupRepository implements PickupRepositoryInterface
{
    /** @var array<int, Pickup> */
    private array $rows = [];

    public function findById(int $requestId): ?Pickup
    {
        return $this->rows[$requestId] ?? null;
    }

    public function save(Pickup $pickup): bool
    {
        $this->rows[$pickup->requestId] = $pickup;
        return true;
    }
}
