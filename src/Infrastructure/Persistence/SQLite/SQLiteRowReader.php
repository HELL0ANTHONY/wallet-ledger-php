<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Persistence\SQLite;

use RuntimeException;

use function is_int;
use function is_string;

final readonly class SQLiteRowReader
{
    /**
     * @param array<mixed> $row
     */
    public function string(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (!is_string($value)) {
            throw new RuntimeException("Invalid SQLite string column: {$key}");
        }

        return $value;
    }

    /**
     * @param array<mixed> $row
     */
    public function int(array $row, string $key): int
    {
        $value = $row[$key] ?? null;
        if (!is_int($value)) {
            throw new RuntimeException("Invalid SQLite integer column: {$key}");
        }

        return $value;
    }
}
