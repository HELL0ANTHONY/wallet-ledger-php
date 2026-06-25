<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Money;

use WalletLedger\Domain\Money\Exception\CurrencyMismatch;
use WalletLedger\Domain\Money\Exception\InvalidMoneyAmount;

final readonly class Money
{
    private function __construct(
        public int $amount,
        public Currency $currency,
    ) {
        if ($this->amount < 0) {
            throw InvalidMoneyAmount::negative($this->amount);
        }
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public static function positive(int $amount, Currency $currency): self
    {
        if ($amount <= 0) {
            throw InvalidMoneyAmount::notPositive($amount);
        }

        return new self($amount, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }
    }
}
