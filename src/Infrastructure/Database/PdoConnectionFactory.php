<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Database;

use PDO;
use WalletLedger\Infrastructure\Config\DatabaseConfig;

final readonly class PdoConnectionFactory
{
    public function __construct(
        private DatabaseConfig $databaseConfig,
    ) {}

    public function create(): PDO
    {
        $pdo = new PDO($this->databaseConfig->dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($this->isSqliteDsn($this->databaseConfig->dsn)) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $pdo;
    }

    private function isSqliteDsn(string $dsn): bool
    {
        return str_starts_with($dsn, 'sqlite:');
    }
}
