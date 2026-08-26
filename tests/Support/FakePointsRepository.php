<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\TransactionType;
use App\Repositories\PointsRepositoryInterface;

final class FakePointsRepository implements PointsRepositoryInterface
{
    public ?int $lastUserId = null;
    public ?int $lastRequestId = null;
    public ?int $lastDropoffId = null;
    public ?int $lastAmount = null;
    public ?string $lastDescription = null;

    public function award(
        int $userId,
        ?int $requestId,
        ?int $dropoffId,
        int $points,
        TransactionType $type,
        ?string $description,
    ): bool {
        $this->lastUserId = $userId;
        $this->lastRequestId = $requestId;
        $this->lastDropoffId = $dropoffId;
        $this->lastAmount = $points;
        $this->lastDescription = $description;
        return true;
    }

    public function balanceForUser(int $userId): int
    {
        return 0;
    }
}
