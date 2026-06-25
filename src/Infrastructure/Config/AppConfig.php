<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

final readonly class AppConfig
{
    public function __construct(
        public AppEnvironment $environment,
        public bool $debug,
        public int $port,
    ) {
        if ($this->port < 1 || $this->port > 65_535) {
            throw InvalidConfigurationException::invalid(EnvironmentVariables::APP_PORT, 'a TCP port between 1 and 65535');
        }
    }
}
