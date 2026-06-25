<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

use WalletLedger\Infrastructure\Shared\Exception\InfrastructureException;

final class InvalidConfigurationException extends InfrastructureException
{
    public static function missing(string $name): self
    {
        return new self("Missing required environment variable: {$name}");
    }

    public static function invalid(string $name, string $expected): self
    {
        return new self("Invalid environment variable {$name}; expected {$expected}.");
    }
}
