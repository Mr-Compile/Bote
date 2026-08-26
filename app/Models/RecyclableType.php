<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\TypeCaster;
use App\Enums\Unit;

final class RecyclableType
{
    public function __construct(
        public readonly int $typeId,
        public readonly string $name,
        public readonly Unit $unit,
        public readonly ?float $pointsPerKg,
        public readonly ?float $pointsPerUnit,
        public readonly ?string $image,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            typeId: TypeCaster::int($data['type_id'], 'type_id'),
            name: TypeCaster::string($data['name'], 'name'),
            unit: Unit::tryFrom(TypeCaster::string($data['unit'], 'unit')) ?? Unit::Kg,
            pointsPerKg: TypeCaster::nullableFloat($data['points_per_kg'], 'points_per_kg'),
            pointsPerUnit: TypeCaster::nullableFloat($data['points_per_unit'], 'points_per_unit'),
            image: TypeCaster::nullableString($data['image'], 'image'),
        );
    }
}
