<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Money\Exception;

use WalletLedger\Domain\Shared\Exception\DomainException;

final class InvalidCurrency extends DomainException
{
    public static function forCode(string $code): self
    {
        return new self("Invalid currency code: {$code}");
    }
}
