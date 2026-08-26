<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ConnectionInterface;
use App\Enums\PickupStatus;
use App\Enums\Unit;
use App\Models\Pickup;
use App\Repositories\PickupRepositoryInterface;
use App\Repositories\PointsRepositoryInterface;

final class PickupService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly PickupRepositoryInterface $pickups,
        private readonly PointsRepositoryInterface $points,
        private readonly PointsService $calculator,
    ) {}

    public function complete(
        int $requestId,
        int $workerId,
        float $actualWeight,
        ?int $actualQuantity,
        ?string $description,
    ): Pickup {
        return $this->completeFor($requestId, $actualWeight, $actualQuantity, $description, $workerId);
    }

    public function completeAsAdmin(
        int $requestId,
        float $actualWeight,
        ?int $actualQuantity,
        ?string $description,
    ): Pickup {
        return $this->completeFor($requestId, $actualWeight, $actualQuantity, $description, null);
    }

    private function completeFor(
        int $requestId,
        float $actualWeight,
        ?int $actualQuantity,
        ?string $description,
        ?int $workerId,
    ): Pickup {
        $pickup = $this->pickups->findById($requestId);

        if ($pickup === null) {
            throw new \DomainException('Pickup request not found');
        }

        if ($workerId !== null && $pickup->assignedWorkerId !== $workerId) {
            throw new \DomainException('Pickup is not assigned to this worker');
        }

        if ($pickup->status !== PickupStatus::Scheduled) {
            throw new \DomainException('Pickup cannot be completed because it is not scheduled');
        }

        $this->guardInputAgainstUnit($pickup, $actualWeight, $actualQuantity);

        $effectiveQuantity = $this->effectiveQuantity($pickup, $actualQuantity);

        $points = $this->calculator->calculate(
            $pickup->recyclableType,
            $actualWeight,
            $effectiveQuantity,
        );

        $completed = $pickup->completed(
            actualWeight: $actualWeight,
            quantityPcs: $effectiveQuantity,
            pointsEarned: $points,
        );

        try {
            $this->db->beginTransaction();
            $this->pickups->save($completed);
            $this->points->award(
                userId: $completed->residentId,
                requestId: $completed->requestId,
                dropoffId: null,
                points: $points,
                type: \App\Enums\TransactionType::Earned,
                description: $description,
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $completed;
    }

    private function guardInputAgainstUnit(Pickup $pickup, float $actualWeight, ?int $actualQuantity): void
    {
        if ($actualWeight <= 0.0) {
            throw new \InvalidArgumentException('Weight must be greater than zero');
        }

        if ($pickup->recyclableType->unit === Unit::Pcs) {
            $effective = $actualQuantity ?? $pickup->quantityPcs;

            if ($effective === null || $effective <= 0) {
                throw new \InvalidArgumentException('Quantity is required for piece-based materials');
            }
        }
    }

    private function effectiveQuantity(Pickup $pickup, ?int $actualQuantity): ?int
    {
        if ($pickup->recyclableType->unit === Unit::Pcs) {
            return $actualQuantity ?? $pickup->quantityPcs;
        }

        return $actualQuantity ?? $pickup->quantityPcs;
    }
}
