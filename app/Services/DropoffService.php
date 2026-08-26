<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ConnectionInterface;
use App\Enums\DropoffStatus;
use App\Enums\TransactionType;
use App\Models\Dropoff;
use App\Repositories\DropoffRepositoryInterface;
use App\Repositories\PointsRepositoryInterface;

final class DropoffService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly DropoffRepositoryInterface $dropoffs,
        private readonly PointsRepositoryInterface $points,
    ) {}

    public function verify(int $dropoffId, int $staffId, string $notes): Dropoff
    {
        $dropoff = $this->findOrFail($dropoffId);

        $verified = $dropoff->verified($staffId, $notes);

        try {
            $this->db->beginTransaction();
            $this->dropoffs->save($verified);
            $this->points->award(
                userId: $verified->residentId,
                requestId: null,
                dropoffId: $verified->dropoffId,
                points: $verified->pointsEarned,
                type: TransactionType::Earned,
                description: "Drop-off verified - {$verified->recyclableType->name}",
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $verified;
    }

    public function reject(int $dropoffId, int $staffId, string $reason): Dropoff
    {
        $dropoff = $this->findOrFail($dropoffId);

        $rejected = $dropoff->rejected($staffId, $reason);

        try {
            $this->db->beginTransaction();
            $this->dropoffs->save($rejected);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $rejected;
    }

    private function findOrFail(int $dropoffId): Dropoff
    {
        $dropoff = $this->dropoffs->findById($dropoffId);

        if ($dropoff === null) {
            throw new \DomainException('Drop-off not found');
        }

        if ($dropoff->status !== DropoffStatus::Pending) {
            throw new \DomainException('Drop-off is not pending');
        }

        return $dropoff;
    }
}
