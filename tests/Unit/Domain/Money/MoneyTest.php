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

    public function test_it_subtracts_money_with_same_currency(): void
    {
        $result = Money::positive(3000, new Currency('ARS'))
            ->subtract(Money::positive(1000, new Currency('ARS')));

        self::assertSame(2000, $result->amount);
    }

    public function test_it_subtracts_to_zero(): void
    {
        $result = Money::positive(1000, new Currency('ARS'))
            ->subtract(Money::positive(1000, new Currency('ARS')));

        self::assertSame(0, $result->amount);
    }

    public function test_it_compares_amounts_with_is_less_than(): void
    {
        $small = Money::positive(500, new Currency('ARS'));
        $large = Money::positive(1000, new Currency('ARS'));

        self::assertTrue($small->isLessThan($large));
        self::assertFalse($large->isLessThan($small));
        self::assertFalse($small->isLessThan($small));
    }

    public function test_it_checks_equality(): void
    {
        $a = Money::positive(1000, new Currency('ARS'));
        $b = Money::positive(1000, new Currency('ARS'));
        $c = Money::positive(2000, new Currency('ARS'));
        $d = Money::positive(1000, new Currency('USD'));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertFalse($a->equals($d));
    }
}
