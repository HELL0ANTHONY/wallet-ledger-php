<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Database;

use PDO;
use WalletLedger\Infrastructure\Shared\Exception\InfrastructureException;

use function file_get_contents;
use function is_string;

final readonly class SchemaInitializer
{
    public function __construct(
        private PDO $pdo,
        private string $schemaPath,
    ) {}

    public function initialize(): void
    {
        $schema = file_get_contents($this->schemaPath);
        if (!is_string($schema)) {
            throw new class ("Unable to read database schema: {$this->schemaPath}") extends InfrastructureException {};
        }

        $this->pdo->exec($schema);
    }
}
