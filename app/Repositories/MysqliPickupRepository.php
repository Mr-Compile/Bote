<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ConnectionInterface;
use App\Core\TypeCaster;
use App\Models\Pickup;
use App\Models\RecyclableType;

final class MysqliPickupRepository implements PickupRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function findById(int $requestId): ?Pickup
    {
        $sql = <<<'SQL'
            SELECT
                pr.*,
                rt.name as type_name,
                rt.unit as type_unit,
                rt.points_per_kg as type_points_per_kg,
                rt.points_per_unit as type_points_per_unit,
                rt.image as type_image
            FROM pickuprequests pr
            JOIN recyclabletypes rt ON pr.type_id = rt.type_id
            WHERE pr.request_id = ?
            LIMIT 1
        SQL;

        $row = $this->db->selectOne($sql, [$requestId], 'i');

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(Pickup $pickup): bool
    {
        $sql = <<<'SQL'
            UPDATE pickuprequests
            SET
                status = ?,
                actual_weight = ?,
                quantity_pcs = ?,
                points_earned = ?,
                updated_at = NOW()
            WHERE request_id = ?
        SQL;

        return $this->db->execute($sql, [
            $pickup->status->value,
            $pickup->actualWeight,
            $pickup->quantityPcs,
            $pickup->pointsEarned,
            $pickup->requestId,
        ], 'sdiii');
    }

    public function assign(int $requestId, int $workerId): bool
    {
        $sql = <<<'SQL'
            UPDATE pickuprequests
            SET
                assigned_worker_id = ?,
                status = 'Scheduled',
                updated_at = NOW()
            WHERE request_id = ?
              AND status = 'Pending'
              AND assigned_worker_id IS NULL
        SQL;

        return $this->db->execute($sql, [$workerId, $requestId], 'ii');
    }

    public function create(
        int $residentId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
        string $preferredDate,
        string $pickupType,
        ?float $latitude,
        ?float $longitude,
        ?int $pointId,
    ): int {
        $sql = <<<'SQL'
            INSERT INTO pickuprequests
                (resident_id, type_id, weight_kg, quantity_pcs, preferred_date, pickup_type, latitude, longitude, point_id, status, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
        SQL;

        return $this->db->insert($sql, [
            $residentId,
            $typeId,
            $weightKg,
            $quantityPcs,
            $preferredDate,
            $pickupType,
            $latitude,
            $longitude,
            $pointId,
        ], 'iidissdii');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Pickup
    {
        $type = RecyclableType::fromArray([
            'type_id' => TypeCaster::int($row['type_id'], 'type_id'),
            'name' => TypeCaster::string($row['type_name'], 'type_name'),
            'unit' => TypeCaster::string($row['type_unit'], 'type_unit'),
            'points_per_kg' => TypeCaster::nullableFloat($row['type_points_per_kg'], 'type_points_per_kg'),
            'points_per_unit' => TypeCaster::nullableFloat($row['type_points_per_unit'], 'type_points_per_unit'),
            'image' => TypeCaster::nullableString($row['type_image'], 'type_image'),
        ]);

        return Pickup::fromArray($row, $type);
    }
}
