<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Domain\Account;

use PHPUnit\Framework\TestCase;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Account\Exception\InsufficientFunds;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Exception\CurrencyMismatch;
use WalletLedger\Domain\Money\Money;

final class AccountTest extends TestCase
{
    public function test_it_opens_account_with_zero_balance(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));

        self::assertSame(0, $account->balance()->amount);
        self::assertSame('ARS', $account->balance()->currency->code);
    }

    public function test_it_deposits_money(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));

        $balance = $account->deposit(Money::positive(15000, new Currency('ARS')));

        self::assertSame(15000, $balance->amount);
    }

    public function test_it_withdraws_money(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));
        $account->deposit(Money::positive(15000, new Currency('ARS')));

        $balance = $account->withdraw(Money::positive(5000, new Currency('ARS')));

        self::assertSame(10000, $balance->amount);
    }

    public function test_it_rejects_withdrawal_without_enough_funds(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));

        $this->expectException(InsufficientFunds::class);

        $account->withdraw(Money::positive(5000, new Currency('ARS')));
    }

    public function test_it_rejects_deposit_with_different_currency(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));

        $this->expectException(CurrencyMismatch::class);

        $account->deposit(Money::positive(5000, new Currency('USD')));
    }
}
