<?php

declare(strict_types=1);

namespace App\Core;

interface ConnectionInterface
{
    /**
     * @param array<int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function query(string $sql, array $params = [], string $types = ''): array;

    /**
     * @param array<int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $params = [], string $types = ''): ?array;

    /**
     * @param array<int, mixed> $params
     */
    public function execute(string $sql, array $params = [], string $types = ''): bool;

    /**
     * @param array<int, mixed> $params
     */
    public function insert(string $sql, array $params = [], string $types = ''): int;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;
}
