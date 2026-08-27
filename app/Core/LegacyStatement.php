<?php

declare(strict_types=1);

namespace App\Core;

final class LegacyStatement
{
    private string $types = '';

    /** @var list<mixed> */
    private array $params = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    private ?LegacyResult $result = null;

    public ?int $insert_id = null;
    public int $affected_rows = 0;

    public function __construct(
        private readonly Legacy $legacy,
        private readonly ConnectionInterface $db,
        private readonly string $sql,
    ) {}

    public function bind_param(string $types, mixed ...$params): void
    {
        $this->types = $types;
        $this->params = array_values($params);
    }

    public function execute(): bool
    {
        $sql = ltrim($this->sql);

        if ($this->isSelect($sql)) {
            $this->rows = $this->db->query($sql, $this->params, $this->types);
            $this->result = null;

            return true;
        }

        if ($this->isInsert($sql)) {
            $id = $this->db->insert($sql, $this->params, $this->types);
            $this->insert_id = $id;
            $this->legacy->last_insert_id = $id;
            $this->affected_rows = (int) $this->db->connection()->affected_rows;
            $this->legacy->last_statement_affected_rows = $this->affected_rows;

            return $this->affected_rows > 0;
        }

        $ok = $this->db->execute($sql, $this->params, $this->types);
        $this->affected_rows = $ok ? (int) $this->db->connection()->affected_rows : 0;
        $this->legacy->last_statement_affected_rows = $this->affected_rows;

        return $ok;
    }

    public function get_result(): LegacyResult
    {
        if ($this->result === null) {
            $this->result = new LegacyResult($this->rows);
        }

        return $this->result;
    }

    public function close(): void
    {
        // Statement is not a real resource; nothing to close.
    }

    public function error(): string
    {
        return '';
    }

    private function isSelect(string $sql): bool
    {
        return (bool) preg_match('/^(SELECT|WITH|SHOW|DESCRIBE|EXPLAIN)\s/i', $sql);
    }

    private function isInsert(string $sql): bool
    {
        return (bool) preg_match('/^INSERT\s/i', $sql);
    }
}
