<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Dropoff;
use App\Repositories\DropoffRepositoryInterface;

final class FakeDropoffRepository implements DropoffRepositoryInterface
{
    /** @var array<int, Dropoff> */
    private array $rows = [];

    public function findById(int $dropoffId): ?Dropoff
    {
        return $this->rows[$dropoffId] ?? null;
    }

    public function save(Dropoff $dropoff): bool
    {
        $this->rows[$dropoff->dropoffId] = $dropoff;
        return true;
    }
}
