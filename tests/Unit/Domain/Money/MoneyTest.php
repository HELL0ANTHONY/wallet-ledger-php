<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Domain\Money;

use PHPUnit\Framework\TestCase;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Exception\CurrencyMismatch;
use WalletLedger\Domain\Money\Exception\InvalidCurrency;
use WalletLedger\Domain\Money\Exception\InvalidMoneyAmount;
use WalletLedger\Domain\Money\Money;

final class MoneyTest extends TestCase
{
    public function test_it_creates_positive_money(): void
    {
        $money = Money::positive(15000, new Currency('ARS'));

        self::assertSame(15000, $money->amount);
        self::assertSame('ARS', $money->currency->code);
    }

    public function test_it_rejects_zero_for_positive_money(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::positive(0, new Currency('ARS'));
    }

    public function test_it_rejects_negative_money(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::positive(-1, new Currency('ARS'));
    }

    public function test_it_rejects_invalid_currency_code(): void
    {
        $this->expectException(InvalidCurrency::class);

        new Currency('ars');
    }

    public function test_it_adds_money_with_same_currency(): void
    {
        $result = Money::positive(1000, new Currency('ARS'))
            ->add(Money::positive(2500, new Currency('ARS')));

        self::assertSame(3500, $result->amount);
    }

    public function test_it_rejects_operations_with_different_currencies(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Money::positive(1000, new Currency('ARS'))
            ->add(Money::positive(1000, new Currency('USD')));
    }
}
