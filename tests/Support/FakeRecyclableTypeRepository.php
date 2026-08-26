<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\Unit;
use App\Models\RecyclableType;
use App\Repositories\RecyclableTypeRepositoryInterface;

final class FakeRecyclableTypeRepository implements RecyclableTypeRepositoryInterface
{
    /** @var array<int, RecyclableType> */
    private array $types = [];

    public function add(RecyclableType $type): void
    {
        $this->types[$type->typeId] = $type;
    }

    public function findById(int $typeId): ?RecyclableType
    {
        return $this->types[$typeId] ?? null;
    }

    public function all(): array
    {
        return array_values($this->types);
    }
}
