<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\TypeCaster;
use App\Enums\DropoffStatus;

final class Dropoff
{
    public function __construct(
        public readonly int $dropoffId,
        public readonly int $residentId,
        public readonly int $pointId,
        public readonly int $typeId,
        public readonly RecyclableType $recyclableType,
        public readonly float $weightKg,
        public readonly ?int $quantityPcs,
        public readonly int $pointsEarned,
        public readonly DropoffStatus $status,
        public readonly ?int $verifiedBy,
        public readonly ?string $verificationNotes,
        public readonly string $createdAt,
        public readonly ?string $verifiedAt,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, RecyclableType $type): self
    {
        return new self(
            dropoffId: TypeCaster::int($data['dropoff_id'], 'dropoff_id'),
            residentId: TypeCaster::int($data['resident_id'], 'resident_id'),
            pointId: TypeCaster::int($data['point_id'], 'point_id'),
            typeId: TypeCaster::int($data['type_id'], 'type_id'),
            recyclableType: $type,
            weightKg: TypeCaster::float($data['weight_kg'], 'weight_kg'),
            quantityPcs: TypeCaster::nullableInt($data['quantity_pcs'], 'quantity_pcs'),
            pointsEarned: TypeCaster::int($data['points_earned'], 'points_earned'),
            status: DropoffStatus::tryFrom(TypeCaster::string($data['status'], 'status')) ?? DropoffStatus::Pending,
            verifiedBy: TypeCaster::nullableInt($data['verified_by'], 'verified_by'),
            verificationNotes: TypeCaster::nullableString($data['verification_notes'], 'verification_notes'),
            createdAt: TypeCaster::string($data['created_at'], 'created_at'),
            verifiedAt: TypeCaster::nullableString($data['verified_at'], 'verified_at'),
        );
    }

    public function verified(int $verifiedBy, string $notes): self
    {
        if ($this->status !== DropoffStatus::Pending) {
            throw new \DomainException('Drop-off is not pending');
        }

        return new self(
            dropoffId: $this->dropoffId,
            residentId: $this->residentId,
            pointId: $this->pointId,
            typeId: $this->typeId,
            recyclableType: $this->recyclableType,
            weightKg: $this->weightKg,
            quantityPcs: $this->quantityPcs,
            pointsEarned: $this->pointsEarned,
            status: DropoffStatus::Completed,
            verifiedBy: $verifiedBy,
            verificationNotes: $notes,
            createdAt: $this->createdAt,
            verifiedAt: date('Y-m-d H:i:s'),
        );
    }

    public function rejected(int $verifiedBy, string $reason): self
    {
        if ($this->status !== DropoffStatus::Pending) {
            throw new \DomainException('Drop-off is not pending');
        }

        return new self(
            dropoffId: $this->dropoffId,
            residentId: $this->residentId,
            pointId: $this->pointId,
            typeId: $this->typeId,
            recyclableType: $this->recyclableType,
            weightKg: $this->weightKg,
            quantityPcs: $this->quantityPcs,
            pointsEarned: $this->pointsEarned,
            status: DropoffStatus::Rejected,
            verifiedBy: $verifiedBy,
            verificationNotes: $reason,
            createdAt: $this->createdAt,
            verifiedAt: date('Y-m-d H:i:s'),
        );
    }
}
