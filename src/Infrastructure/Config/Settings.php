<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

final readonly class Settings
{
    public function __construct(
        public AppConfig $app,
        public DatabaseConfig $database,
    ) {}
}
