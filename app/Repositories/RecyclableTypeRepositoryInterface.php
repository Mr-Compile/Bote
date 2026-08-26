<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\RecyclableType;

interface RecyclableTypeRepositoryInterface
{
    public function findById(int $typeId): ?RecyclableType;

    /** @return list<RecyclableType> */
    public function all(): array;
}
