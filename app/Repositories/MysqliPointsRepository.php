<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ConnectionInterface;
use App\Core\TypeCaster;
use App\Enums\TransactionType;

final class MysqliPointsRepository implements PointsRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function award(
        int $userId,
        ?int $requestId,
        ?int $dropoffId,
        int $points,
        TransactionType $type,
        ?string $description,
    ): bool {
        $sql = <<<'SQL'
            INSERT INTO pointsledger
                (user_id, request_id, dropoff_id, points, transaction_type, description)
            VALUES
                (?, ?, ?, ?, ?, ?)
        SQL;

        return $this->db->execute($sql, [
            $userId,
            $requestId,
            $dropoffId,
            $points,
            $type->value,
            $description,
        ], 'iiiiss');
    }

    public function balanceForUser(int $userId): int
    {
        $sql = <<<'SQL'
            SELECT COALESCE(SUM(CASE
                WHEN transaction_type = 'Earned' THEN points
                WHEN transaction_type IN ('Redeemed', 'Refunded') THEN -points
                ELSE 0
            END), 0) as balance
            FROM pointsledger
            WHERE user_id = ?
        SQL;

        $row = $this->db->selectOne($sql, [$userId], 'i');

        if ($row === null) {
            return 0;
        }

        return TypeCaster::int($row['balance'], 'balance');
    }
}
