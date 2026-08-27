<?php

declare(strict_types=1);

namespace App\Core;

final class Database implements ConnectionInterface
{
    public function __construct(private readonly \mysqli $mysqli) {}

    public function connection(): \mysqli
    {
        return $this->mysqli;
    }

    /**
     * @param array<int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function query(string $sql, array $params = [], string $types = ''): array
    {
        $stmt = $this->prepare($sql, $params, $types);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function selectOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $rows = $this->query($sql, $params, $types);

        return $rows[0] ?? null;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function execute(string $sql, array $params = [], string $types = ''): bool
    {
        $stmt = $this->prepare($sql, $params, $types);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function insert(string $sql, array $params = [], string $types = ''): int
    {
        $stmt = $this->prepare($sql, $params, $types);
        $stmt->execute();
        $id = (int) $this->mysqli->insert_id;
        $stmt->close();

        return $id;
    }

    public function beginTransaction(): bool
    {
        return $this->mysqli->begin_transaction();
    }

    public function commit(): bool
    {
        return $this->mysqli->commit();
    }

    public function rollback(): bool
    {
        return $this->mysqli->rollback();
    }

    /**
     * @param array<int, mixed> $params
     */
    private function prepare(string $sql, array $params, string $types): \mysqli_stmt
    {
        $stmt = $this->mysqli->prepare($sql);

        if ($stmt === false) {
            throw new \RuntimeException('Failed to prepare statement: ' . $this->mysqli->error);
        }

        if ($params !== [] && $types !== '') {
            $this->bind($stmt, $params, $types);
        }

        return $stmt;
    }

    /**
     * @param array<int, mixed> $params
     */
    private function bind(\mysqli_stmt $stmt, array $params, string $types): void
    {
        $values = [];
        $bindTypes = '';

        foreach ($params as $index => $value) {
            $typeChar = $types[$index] ?? 's';

            if ($value === null) {
                $values[] = null;
                $bindTypes .= 's';
                continue;
            }

            $values[] = $value;
            $bindTypes .= $typeChar;
        }

        if ($values !== []) {
            $stmt->bind_param($bindTypes, ...$values);
        }
    }
}
