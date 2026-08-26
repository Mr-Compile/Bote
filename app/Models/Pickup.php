<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\TypeCaster;
use App\Enums\PickupStatus;

final class Pickup
{
    public function __construct(
        public readonly int $requestId,
        public readonly int $residentId,
        public readonly int $typeId,
        public readonly ?int $assignedWorkerId,
        public readonly RecyclableType $recyclableType,
        public readonly float $weightKg,
        public readonly ?int $quantityPcs,
        public readonly string $preferredDate,
        public readonly PickupStatus $status,
        public readonly string $pickupType,
        public readonly ?int $pointId,
        public readonly ?float $actualWeight,
        public readonly ?int $pointsEarned,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, RecyclableType $type): self
    {
        return new self(
            requestId: TypeCaster::int($data['request_id'], 'request_id'),
            residentId: TypeCaster::int($data['resident_id'], 'resident_id'),
            typeId: TypeCaster::int($data['type_id'], 'type_id'),
            assignedWorkerId: TypeCaster::nullableInt($data['assigned_worker_id'], 'assigned_worker_id'),
            recyclableType: $type,
            weightKg: TypeCaster::float($data['weight_kg'], 'weight_kg'),
            quantityPcs: TypeCaster::nullableInt($data['quantity_pcs'], 'quantity_pcs'),
            preferredDate: TypeCaster::string($data['preferred_date'], 'preferred_date'),
            status: PickupStatus::tryFrom(TypeCaster::string($data['status'], 'status')) ?? PickupStatus::Pending,
            pickupType: TypeCaster::string($data['pickup_type'], 'pickup_type'),
            pointId: TypeCaster::nullableInt($data['point_id'], 'point_id'),
            actualWeight: TypeCaster::nullableFloat($data['actual_weight'], 'actual_weight'),
            pointsEarned: TypeCaster::nullableInt($data['points_earned'], 'points_earned'),
            latitude: TypeCaster::nullableFloat($data['latitude'], 'latitude'),
            longitude: TypeCaster::nullableFloat($data['longitude'], 'longitude'),
            createdAt: TypeCaster::string($data['created_at'], 'created_at'),
            updatedAt: TypeCaster::string($data['updated_at'], 'updated_at'),
        );
    }

    public function completed(float $actualWeight, ?int $quantityPcs, int $pointsEarned): self
    {
        if ($this->status === PickupStatus::Completed) {
            throw new \DomainException('Pickup is already completed');
        }

        return new self(
            requestId: $this->requestId,
            residentId: $this->residentId,
            typeId: $this->typeId,
            assignedWorkerId: $this->assignedWorkerId,
            recyclableType: $this->recyclableType,
            weightKg: $this->weightKg,
            quantityPcs: $quantityPcs ?? $this->quantityPcs,
            preferredDate: $this->preferredDate,
            status: PickupStatus::Completed,
            pickupType: $this->pickupType,
            pointId: $this->pointId,
            actualWeight: $actualWeight,
            pointsEarned: $pointsEarned,
            latitude: $this->latitude,
            longitude: $this->longitude,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
