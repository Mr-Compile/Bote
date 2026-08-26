<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Unit;
use App\Models\RecyclableType;
use PHPUnit\Framework\TestCase;

final class RecyclableTypeTest extends TestCase
{
    public function test_from_array_creates_kg_type(): void
    {
        $data = [
            'type_id' => 1,
            'name' => 'Plastic Bottles',
            'unit' => 'kg',
            'points_per_kg' => 10.0,
            'points_per_unit' => null,
            'image' => 'rewards/plastic.png',
        ];

        $type = RecyclableType::fromArray($data);

        self::assertSame(1, $type->typeId);
        self::assertSame('Plastic Bottles', $type->name);
        self::assertSame(Unit::Kg, $type->unit);
        self::assertSame(10.0, $type->pointsPerKg);
        self::assertNull($type->pointsPerUnit);
        self::assertSame('rewards/plastic.png', $type->image);
    }

    public function test_from_array_creates_pcs_type(): void
    {
        $data = [
            'type_id' => 2,
            'name' => 'Small Glass Bottles',
            'unit' => 'pcs',
            'points_per_kg' => null,
            'points_per_unit' => 1.0,
            'image' => null,
        ];

        $type = RecyclableType::fromArray($data);

        self::assertSame(Unit::Pcs, $type->unit);
        self::assertSame(1.0, $type->pointsPerUnit);
    }
}
