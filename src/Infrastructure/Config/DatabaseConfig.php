<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

final readonly class DatabaseConfig
{
    public function __construct(
        public string $dsn,
        public string $path,
    ) {
        if ($this->dsn === '') {
            throw InvalidConfigurationException::invalid(EnvironmentVariables::DATABASE_DSN, 'a non-empty PDO DSN');
        }

        if ($this->path === '') {
            throw InvalidConfigurationException::invalid(EnvironmentVariables::DATABASE_PATH, 'a non-empty filesystem path');
        }
    }
}
