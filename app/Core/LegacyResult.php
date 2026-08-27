<?php

declare(strict_types=1);

namespace App\Core;

final class LegacyResult
{
    public int $num_rows = 0;
    public int $field_count = 0;
    public bool $success = true;

    /** @var list<array<string, mixed>> */
    private array $rows;

    private int $index = 0;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(array $rows = [], bool $success = true)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
        $this->success = $success;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetch_assoc(): ?array
    {
        return $this->rows[$this->index++] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetch_all(int $mode = MYSQLI_ASSOC): array
    {
        return $mode === MYSQLI_ASSOC ? $this->rows : [];
    }

    /**
     * @return list<mixed>|false
     */
    public function fetch_row(): array|false
    {
        $row = $this->rows[$this->index++] ?? null;

        return $row === null ? false : array_values($row);
    }

    public function data_seek(int $offset): bool
    {
        if ($offset >= 0 && $offset < $this->num_rows) {
            $this->index = $offset;

            return true;
        }

        return false;
    }
}
