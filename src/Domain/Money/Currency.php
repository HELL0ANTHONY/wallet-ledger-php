<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Money;

use WalletLedger\Domain\Money\Exception\InvalidCurrency;

use function mb_strtoupper;
use function preg_match;

final readonly class Currency
{
    public function __construct(
        public string $code,
    ) {
        if ($this->code !== mb_strtoupper($this->code) || preg_match('/^[A-Z]{3}$/', $this->code) !== 1) {
            throw InvalidCurrency::forCode($this->code);
        }
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
