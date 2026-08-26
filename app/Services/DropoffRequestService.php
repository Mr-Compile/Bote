<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Unit;
use App\Models\Dropoff;
use App\Models\RecyclableType;
use App\Repositories\DropoffRepositoryInterface;
use App\Repositories\RecyclableTypeRepositoryInterface;

final class DropoffRequestService
{
    public function __construct(
        private readonly DropoffRepositoryInterface $dropoffs,
        private readonly RecyclableTypeRepositoryInterface $types,
        private readonly PointsService $calculator,
    ) {}

    public function create(
        int $residentId,
        int $pointId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
    ): Dropoff {
        $type = $this->findTypeOrFail($typeId);

        [$weightKg, $quantityPcs] = $this->validateMaterial($type, $weightKg, $quantityPcs);

        $pointsEarned = $this->calculator->calculate($type, $weightKg, $quantityPcs);

        $id = $this->dropoffs->create(
            residentId: $residentId,
            pointId: $pointId,
            typeId: $typeId,
            weightKg: $weightKg,
            quantityPcs: $quantityPcs,
            pointsEarned: $pointsEarned,
        );

        $dropoff = $this->dropoffs->findById($id);

        if ($dropoff === null) {
            throw new \RuntimeException('Created drop-off could not be retrieved');
        }

        return $dropoff;
    }

    private function findTypeOrFail(int $typeId): RecyclableType
    {
        $type = $this->types->findById($typeId);

        if ($type === null) {
            throw new \InvalidArgumentException('Invalid recyclable type selected');
        }

        return $type;
    }

    /**
     * @return array{0: float, 1: ?int}
     */
    private function validateMaterial(RecyclableType $type, float $weightKg, ?int $quantityPcs): array
    {
        if ($type->unit === Unit::Kg) {
            if ($weightKg <= 0) {
                throw new \InvalidArgumentException('Weight must be greater than 0');
            }

            if ($weightKg < 10) {
                throw new \InvalidArgumentException('Minimum weight requirement is 10 kilograms');
            }

            return [$weightKg, null];
        }

        if ($quantityPcs === null || $quantityPcs <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        if ($quantityPcs < 30) {
            throw new \InvalidArgumentException('Minimum quantity requirement is 30 pieces');
        }

        return [0.01, $quantityPcs];
    }
}
