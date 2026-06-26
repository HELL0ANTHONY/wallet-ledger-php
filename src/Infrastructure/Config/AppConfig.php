<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

final readonly class AppConfig
{
    public function __construct(
        public AppEnvironment $environment,
        public bool $debug,
    ) {}
}
