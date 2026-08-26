<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Unit;
use App\Models\RecyclableType;

final class PointsService
{
    public function calculate(RecyclableType $type, float $actualWeight, ?int $quantity): int
    {
        return match ($type->unit) {
            Unit::Kg => $this->calculateByWeight($type, $actualWeight),
            Unit::Pcs => $this->calculateByQuantity($type, $quantity),
        };
    }

    private function calculateByWeight(RecyclableType $type, float $actualWeight): int
    {
        if ($actualWeight <= 0.0) {
            throw new \InvalidArgumentException('Weight must be greater than zero for kg-based materials');
        }

        if ($type->pointsPerKg === null) {
            throw new \InvalidArgumentException('Points per kg is not configured for this material');
        }

        return (int) floor($actualWeight * $type->pointsPerKg);
    }

    private function calculateByQuantity(RecyclableType $type, ?int $quantity): int
    {
        if ($quantity === null || $quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero for piece-based materials');
        }

        if ($type->pointsPerUnit === null) {
            throw new \InvalidArgumentException('Points per unit is not configured for this material');
        }

        return (int) floor($quantity * $type->pointsPerUnit);
    }
}
