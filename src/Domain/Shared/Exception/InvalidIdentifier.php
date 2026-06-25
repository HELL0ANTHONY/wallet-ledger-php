<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Shared\Exception;

final class InvalidIdentifier extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self("Invalid identifier: {$value}");
    }
}
