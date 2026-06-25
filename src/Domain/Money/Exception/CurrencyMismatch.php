<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Money\Exception;

use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Shared\Exception\DomainException;

final class CurrencyMismatch extends DomainException
{
    public static function between(Currency $left, Currency $right): self
    {
        return new self("Currency mismatch: {$left->code} does not match {$right->code}");
    }
}
