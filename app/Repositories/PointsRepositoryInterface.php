<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TransactionType;

interface PointsRepositoryInterface
{
    public function award(
        int $userId,
        ?int $requestId,
        ?int $dropoffId,
        int $points,
        TransactionType $type,
        ?string $description,
    ): bool;

    public function balanceForUser(int $userId): int;
}
