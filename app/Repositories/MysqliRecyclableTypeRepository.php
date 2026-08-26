<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ConnectionInterface;
use App\Models\RecyclableType;

final class MysqliRecyclableTypeRepository implements RecyclableTypeRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function findById(int $typeId): ?RecyclableType
    {
        $row = $this->db->selectOne(
            'SELECT * FROM recyclabletypes WHERE type_id = ? LIMIT 1',
            [$typeId],
            'i',
        );

        if ($row === null) {
            return null;
        }

        return RecyclableType::fromArray($row);
    }

    public function all(): array
    {
        $rows = $this->db->query('SELECT * FROM recyclabletypes ORDER BY name ASC');

        $types = [];
        foreach ($rows as $row) {
            $types[] = RecyclableType::fromArray($row);
        }

        return $types;
    }
}
