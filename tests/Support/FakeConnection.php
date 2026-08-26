<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\ConnectionInterface;

final class FakeConnection implements ConnectionInterface
{
    /** @var list<array<string, mixed>> */
    public array $lastRows = [];
    public string $lastSql = '';

    /**
     * @param array<int, mixed> $params
     */
    public function query(string $sql, array $params = [], string $types = ''): array
    {
        $this->lastSql = $sql;
        return $this->lastRows;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function selectOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $this->lastSql = $sql;
        return $this->lastRows[0] ?? null;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function execute(string $sql, array $params = [], string $types = ''): bool
    {
        $this->lastSql = $sql;
        return true;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function insert(string $sql, array $params = [], string $types = ''): int
    {
        $this->lastSql = $sql;
        return 1;
    }

    public function beginTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollback(): bool
    {
        return true;
    }
}
