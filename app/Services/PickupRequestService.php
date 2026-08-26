<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PickupStatus;
use App\Enums\Unit;
use App\Models\Pickup;
use App\Models\RecyclableType;
use App\Repositories\PickupRepositoryInterface;
use App\Repositories\RecyclableTypeRepositoryInterface;

final class PickupRequestService
{
    public function __construct(
        private readonly PickupRepositoryInterface $pickups,
        private readonly RecyclableTypeRepositoryInterface $types,
    ) {}

    public function create(
        int $residentId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
        string $preferredDate,
        string $pickupType,
        ?int $pointId,
        ?float $latitude,
        ?float $longitude,
    ): Pickup {
        $type = $this->findTypeOrFail($typeId);

        [$weightKg, $quantityPcs] = $this->validateMaterial($type, $weightKg, $quantityPcs);
        $this->validatePickupType($pickupType, $pointId, $latitude, $longitude);

        $id = $this->pickups->create(
            residentId: $residentId,
            typeId: $typeId,
            weightKg: $weightKg,
            quantityPcs: $quantityPcs,
            preferredDate: $preferredDate,
            pickupType: $pickupType,
            latitude: $latitude,
            longitude: $longitude,
            pointId: $pickupType === 'CollectionPoint' ? $pointId : null,
        );

        $pickup = $this->pickups->findById($id);

        if ($pickup === null) {
            throw new \RuntimeException('Created pickup request could not be retrieved');
        }

        return $pickup;
    }

    public function assignToWorker(int $requestId, int $workerId): Pickup
    {
        $pickup = $this->pickups->findById($requestId);

        if ($pickup === null) {
            throw new \DomainException('Pickup request not found');
        }

        if ($pickup->status !== PickupStatus::Pending) {
            throw new \DomainException('Pickup request cannot be assigned because it is not pending');
        }

        if ($pickup->assignedWorkerId !== null && $pickup->assignedWorkerId !== $workerId) {
            throw new \DomainException('Pickup request is already assigned to another worker');
        }

        $this->pickups->assign($requestId, $workerId);

        $assigned = $this->pickups->findById($requestId);

        if ($assigned === null || $assigned->assignedWorkerId !== $workerId) {
            throw new \RuntimeException('Failed to assign pickup request');
        }

        return $assigned;
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

    private function validatePickupType(string $pickupType, ?int $pointId, ?float $latitude, ?float $longitude): void
    {
        if ($pickupType === 'CollectionPoint') {
            if ($pointId === null) {
                throw new \InvalidArgumentException('Collection point must be selected for collection point pickup');
            }

            if ($latitude === null || $longitude === null) {
                throw new \InvalidArgumentException('Collection point coordinates are required');
            }

            return;
        }

        if ($pickupType === 'Home') {
            if ($latitude === null || $longitude === null) {
                throw new \InvalidArgumentException('GPS location is required for home pickup');
            }

            return;
        }

        throw new \InvalidArgumentException('Invalid pickup type selected');
    }
}
