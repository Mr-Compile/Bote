<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ConnectionInterface;
use App\Core\TypeCaster;
use App\Models\Dropoff;
use App\Models\RecyclableType;

final class MysqliDropoffRepository implements DropoffRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function findById(int $dropoffId): ?Dropoff
    {
        $sql = <<<'SQL'
            SELECT
                dd.*,
                rt.name as type_name,
                rt.unit as type_unit,
                rt.points_per_kg as type_points_per_kg,
                rt.points_per_unit as type_points_per_unit,
                rt.image as type_image
            FROM direct_dropoffs dd
            JOIN recyclabletypes rt ON dd.type_id = rt.type_id
            WHERE dd.dropoff_id = ?
            LIMIT 1
        SQL;

        $row = $this->db->selectOne($sql, [$dropoffId], 'i');

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(Dropoff $dropoff): bool
    {
        $sql = <<<'SQL'
            UPDATE direct_dropoffs
            SET
                status = ?,
                verified_by = ?,
                verification_notes = ?,
                verified_at = ?
            WHERE dropoff_id = ?
        SQL;

        return $this->db->execute($sql, [
            $dropoff->status->value,
            $dropoff->verifiedBy,
            $dropoff->verificationNotes,
            $dropoff->verifiedAt,
            $dropoff->dropoffId,
        ], 'sissi');
    }

    public function create(
        int $residentId,
        int $pointId,
        int $typeId,
        float $weightKg,
        ?int $quantityPcs,
        int $pointsEarned,
    ): int {
        $sql = <<<'SQL'
            INSERT INTO direct_dropoffs
                (resident_id, point_id, type_id, weight_kg, quantity_pcs, points_earned, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, 'Pending', NOW())
        SQL;

        return $this->db->insert($sql, [
            $residentId,
            $pointId,
            $typeId,
            $weightKg,
            $quantityPcs,
            $pointsEarned,
        ], 'iiidii');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Dropoff
    {
        $type = RecyclableType::fromArray([
            'type_id' => TypeCaster::int($row['type_id'], 'type_id'),
            'name' => TypeCaster::string($row['type_name'], 'type_name'),
            'unit' => TypeCaster::string($row['type_unit'], 'type_unit'),
            'points_per_kg' => TypeCaster::nullableFloat($row['type_points_per_kg'], 'type_points_per_kg'),
            'points_per_unit' => TypeCaster::nullableFloat($row['type_points_per_unit'], 'type_points_per_unit'),
            'image' => TypeCaster::nullableString($row['type_image'], 'type_image'),
        ]);

        return Dropoff::fromArray($row, $type);
    }
}
