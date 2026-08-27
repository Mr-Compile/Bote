<?php

declare(strict_types=1);

namespace App\Core;

final class Legacy
{
    public int $last_insert_id = 0;
    public int $last_statement_affected_rows = 0;

    public function __construct(private readonly ConnectionInterface $db) {}

    public function prepare(string $sql): LegacyStatement
    {
        return new LegacyStatement($this, $this->db, $sql);
    }

    /**
     * @param array<int, mixed> $params
     */
    public function query(string $sql, array $params = [], string $types = ''): LegacyResult|false
    {
        $sql = ltrim($sql);

        if ($this->isSelect($sql)) {
            return new LegacyResult($this->db->query($sql, $params, $types));
        }

        return $this->db->execute($sql, $params, $types) ? new LegacyResult([]) : false;
    }

    /**
     * @param array<int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $params = [], string $types = ''): ?array
    {
        return $this->db->selectOne($sql, $params, $types);
    }

    /**
     * @param array<int, mixed> $params
     */
    public function execute(string $sql, array $params = [], string $types = ''): bool
    {
        return $this->db->execute($sql, $params, $types);
    }

    /**
     * @param array<int, mixed> $params
     */
    public function insert(string $sql, array $params = [], string $types = ''): int
    {
        $id = $this->db->insert($sql, $params, $types);
        $this->last_insert_id = $id;

        return $id;
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollback();
    }

    private function isSelect(string $sql): bool
    {
        return (bool) preg_match('/^(SELECT|WITH|SHOW|DESCRIBE|EXPLAIN)\s/i', $sql);
    }
}
