<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Account;

use WalletLedger\Domain\Account\Exception\InsufficientFunds;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Money;

final class Account
{
    private Money $balance;

    public function __construct(
        public readonly AccountId $id,
        public readonly Currency $currency,
    ) {
        $this->balance = Money::zero($this->currency);
    }

    public static function open(AccountId $id, Currency $currency): self
    {
        return new self($id, $currency);
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function deposit(Money $amount): Money
    {
        $this->balance = $this->balance->add($amount);

        return $this->balance;
    }

    public function withdraw(Money $amount): Money
    {
        if ($this->balance->isLessThan($amount)) {
            throw InsufficientFunds::forAccount($this->id);
        }

        $this->balance = $this->balance->subtract($amount);

        return $this->balance;
    }
}
