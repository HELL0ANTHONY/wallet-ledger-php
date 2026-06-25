<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Money\Exception;

use WalletLedger\Domain\Shared\Exception\DomainException;

final class InvalidMoneyAmount extends DomainException
{
    public static function negative(int $amount): self
    {
        return new self("Money amount cannot be negative: {$amount}");
    }

    public static function notPositive(int $amount): self
    {
        return new self("Money amount must be greater than zero: {$amount}");
    }
}
