<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Database;

use PDO;
use WalletLedger\Infrastructure\Config\DatabaseConfig;

use function dirname;
use function is_dir;
use function mkdir;
use function str_starts_with;

final readonly class PdoConnectionFactory
{
    public function __construct(
        private DatabaseConfig $databaseConfig,
    ) {}

    public function create(): PDO
    {
        $this->ensureSqliteDirectoryExists();

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

    private function ensureSqliteDirectoryExists(): void
    {
        if (!$this->isSqliteDsn($this->databaseConfig->dsn) || $this->databaseConfig->path === ':memory:') {
            return;
        }

        $directory = dirname($this->databaseConfig->path);
        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }
    }
}
