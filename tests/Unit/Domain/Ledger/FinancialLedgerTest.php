<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Domain\Ledger;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Account\Exception\InsufficientFunds;
use WalletLedger\Domain\Account\Exception\SameAccountTransfer;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Domain\Ledger\LedgerEntryId;
use WalletLedger\Domain\Ledger\LedgerEntryType;
use WalletLedger\Domain\Ledger\OperationId;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Exception\CurrencyMismatch;
use WalletLedger\Domain\Money\Money;

final class FinancialLedgerTest extends TestCase
{
    public function test_it_records_deposit_as_credit_entry(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));
        $createdAt = new DateTimeImmutable('2026-06-25T12:00:00+00:00');

        $entry = (new FinancialLedger())->deposit(
            account: $account,
            entryId: new LedgerEntryId('led_123'),
            operationId: new OperationId('op_123'),
            amount: Money::positive(15000, new Currency('ARS')),
            createdAt: $createdAt,
        );

        self::assertSame(LedgerEntryType::Credit, $entry->type);
        self::assertSame('op_123', $entry->operationId->value);
        self::assertSame(15000, $entry->amount->amount);
        self::assertSame(15000, $entry->balanceAfter->amount);
        self::assertSame(15000, $account->balance()->amount);
    }

    public function test_it_records_withdrawal_as_debit_entry(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));
        $account->deposit(Money::positive(15000, new Currency('ARS')));

        $entry = (new FinancialLedger())->withdraw(
            account: $account,
            entryId: new LedgerEntryId('led_123'),
            operationId: new OperationId('op_123'),
            amount: Money::positive(5000, new Currency('ARS')),
            createdAt: new DateTimeImmutable('2026-06-25T12:00:00+00:00'),
        );

        self::assertSame(LedgerEntryType::Debit, $entry->type);
        self::assertSame(5000, $entry->amount->amount);
        self::assertSame(10000, $entry->balanceAfter->amount);
        self::assertSame(10000, $account->balance()->amount);
    }

    public function test_it_records_transfer_as_debit_and_credit_with_same_operation_id(): void
    {
        $fromAccount = Account::open(new AccountId('acc_123'), new Currency('ARS'));
        $toAccount = Account::open(new AccountId('acc_456'), new Currency('ARS'));
        $fromAccount->deposit(Money::positive(20000, new Currency('ARS')));

        $entries = (new FinancialLedger())->transfer(
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            debitEntryId: new LedgerEntryId('led_debit_123'),
            creditEntryId: new LedgerEntryId('led_credit_123'),
            operationId: new OperationId('op_123'),
            amount: Money::positive(15000, new Currency('ARS')),
            createdAt: new DateTimeImmutable('2026-06-25T12:00:00+00:00'),
        );

        self::assertSame(LedgerEntryType::Debit, $entries->debit->type);
        self::assertSame(LedgerEntryType::Credit, $entries->credit->type);
        self::assertSame('op_123', $entries->debit->operationId->value);
        self::assertSame('op_123', $entries->credit->operationId->value);
        self::assertSame(5000, $entries->debit->balanceAfter->amount);
        self::assertSame(15000, $entries->credit->balanceAfter->amount);
        self::assertSame(5000, $fromAccount->balance()->amount);
        self::assertSame(15000, $toAccount->balance()->amount);
    }

    public function test_it_rejects_transfer_between_same_account(): void
    {
        $account = Account::open(new AccountId('acc_123'), new Currency('ARS'));

        $this->expectException(SameAccountTransfer::class);

        (new FinancialLedger())->transfer(
            fromAccount: $account,
            toAccount: $account,
            debitEntryId: new LedgerEntryId('led_debit_123'),
            creditEntryId: new LedgerEntryId('led_credit_123'),
            operationId: new OperationId('op_123'),
            amount: Money::positive(15000, new Currency('ARS')),
            createdAt: new DateTimeImmutable('2026-06-25T12:00:00+00:00'),
        );
    }

    public function test_it_rejects_transfer_without_enough_funds_before_crediting_destination(): void
    {
        $fromAccount = Account::open(new AccountId('acc_123'), new Currency('ARS'));
        $toAccount = Account::open(new AccountId('acc_456'), new Currency('ARS'));

        $this->expectException(InsufficientFunds::class);

        try {
            (new FinancialLedger())->transfer(
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                debitEntryId: new LedgerEntryId('led_debit_123'),
                creditEntryId: new LedgerEntryId('led_credit_123'),
                operationId: new OperationId('op_123'),
                amount: Money::positive(15000, new Currency('ARS')),
                createdAt: new DateTimeImmutable('2026-06-25T12:00:00+00:00'),
            );
        } finally {
            self::assertSame(0, $fromAccount->balance()->amount);
            self::assertSame(0, $toAccount->balance()->amount);
        }
    }

    public function test_it_rejects_transfer_currency_mismatch_before_mutating_accounts(): void
    {
        $fromAccount = Account::open(new AccountId('acc_123'), new Currency('ARS'));
        $toAccount = Account::open(new AccountId('acc_456'), new Currency('USD'));
        $fromAccount->deposit(Money::positive(20000, new Currency('ARS')));

        $this->expectException(CurrencyMismatch::class);

        try {
            (new FinancialLedger())->transfer(
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                debitEntryId: new LedgerEntryId('led_debit_123'),
                creditEntryId: new LedgerEntryId('led_credit_123'),
                operationId: new OperationId('op_123'),
                amount: Money::positive(15000, new Currency('ARS')),
                createdAt: new DateTimeImmutable('2026-06-25T12:00:00+00:00'),
            );
        } finally {
            self::assertSame(20000, $fromAccount->balance()->amount);
            self::assertSame(0, $toAccount->balance()->amount);
        }
    }
}
