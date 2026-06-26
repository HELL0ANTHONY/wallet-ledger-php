<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Integration\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Account\DTO\GetAccountBalanceInput;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Account\UseCase\GetAccountBalance;
use WalletLedger\Application\Ledger\DTO\DepositFundsInput;
use WalletLedger\Application\Ledger\DTO\ListAccountLedgerEntriesInput;
use WalletLedger\Application\Ledger\DTO\TransferFundsInput;
use WalletLedger\Application\Ledger\DTO\WithdrawFundsInput;
use WalletLedger\Application\Ledger\Exception\IdempotencyConflict;
use WalletLedger\Application\Ledger\UseCase\DepositFunds;
use WalletLedger\Application\Ledger\UseCase\ListAccountLedgerEntries;
use WalletLedger\Application\Ledger\UseCase\TransferFunds;
use WalletLedger\Application\Ledger\UseCase\WithdrawFunds;
use WalletLedger\Domain\Account\Exception\InsufficientFunds;
use WalletLedger\Domain\Account\Exception\SameAccountTransfer;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Infrastructure\Config\DatabaseConfig;
use WalletLedger\Infrastructure\Database\PdoConnectionFactory;
use WalletLedger\Infrastructure\Database\PdoTransactionManager;
use WalletLedger\Infrastructure\Database\SchemaInitializer;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteAccountRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteIdempotencyRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteLedgerEntryRepository;
use WalletLedger\Tests\Unit\Application\Support\FixedClock;
use WalletLedger\Tests\Unit\Application\Support\SequentialIdentifierGenerator;

final class FinancialWorkflowTest extends TestCase
{
    public function test_it_executes_full_financial_workflow_with_sqlite_persistence(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123');
        $this->createAccount($context, 'acc_456');

        $this->depositFunds($context)(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 20000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));
        $this->withdrawFunds($context)(new WithdrawFundsInput(
            accountId: 'acc_123',
            amount: 5000,
            currency: 'ARS',
            idempotencyKey: 'withdrawal-001',
        ));
        $transfer = $this->transferFunds($context)(new TransferFundsInput(
            fromAccountId: 'acc_123',
            toAccountId: 'acc_456',
            amount: 7000,
            currency: 'ARS',
            idempotencyKey: 'transfer-001',
        ));

        $fromBalance = (new GetAccountBalance($context->accounts))(new GetAccountBalanceInput('acc_123'));
        $toBalance = (new GetAccountBalance($context->accounts))(new GetAccountBalanceInput('acc_456'));
        $fromEntries = (new ListAccountLedgerEntries($context->ledgerEntries))(new ListAccountLedgerEntriesInput('acc_123'));
        $toEntries = (new ListAccountLedgerEntries($context->ledgerEntries))(new ListAccountLedgerEntriesInput('acc_456'));

        self::assertSame(8000, $fromBalance->balance);
        self::assertSame(7000, $toBalance->balance);
        self::assertSame(8000, $transfer->balance);
        self::assertCount(3, $fromEntries->entries);
        self::assertCount(1, $toEntries->entries);
        self::assertSame('credit', $fromEntries->entries[0]->type);
        self::assertSame('debit', $fromEntries->entries[1]->type);
        self::assertSame('debit', $fromEntries->entries[2]->type);
        self::assertSame('credit', $toEntries->entries[0]->type);
    }

    public function test_it_returns_stored_result_for_repeated_idempotent_mutation(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123');
        $deposit = $this->depositFunds($context);
        $input = new DepositFundsInput(
            accountId: 'acc_123',
            amount: 10000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        );

        $first = $deposit($input);
        $second = $deposit($input);
        $entries = (new ListAccountLedgerEntries($context->ledgerEntries))(new ListAccountLedgerEntriesInput('acc_123'));
        $balance = (new GetAccountBalance($context->accounts))(new GetAccountBalanceInput('acc_123'));

        self::assertSame($first->operationId, $second->operationId);
        self::assertSame($first->ledgerEntries[0]->ledgerEntryId, $second->ledgerEntries[0]->ledgerEntryId);
        self::assertSame(10000, $balance->balance);
        self::assertCount(1, $entries->entries);
    }

    public function test_it_rejects_business_errors(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123');

        $this->expectException(InsufficientFunds::class);

        $this->withdrawFunds($context)(new WithdrawFundsInput(
            accountId: 'acc_123',
            amount: 5000,
            currency: 'ARS',
            idempotencyKey: 'withdrawal-001',
        ));
    }

    public function test_it_rejects_same_account_transfer(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123');

        $this->expectException(SameAccountTransfer::class);

        $this->transferFunds($context)(new TransferFundsInput(
            fromAccountId: 'acc_123',
            toAccountId: 'acc_123',
            amount: 1000,
            currency: 'ARS',
            idempotencyKey: 'transfer-001',
        ));
    }

    public function test_it_rejects_idempotency_key_reuse_with_different_payload(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123');
        $deposit = $this->depositFunds($context);

        $deposit(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 10000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));

        $this->expectException(IdempotencyConflict::class);

        $deposit(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 20000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));
    }

    private function context(): ApplicationIntegrationContext
    {
        $pdo = (new PdoConnectionFactory(new DatabaseConfig('sqlite::memory:', ':memory:')))->create();
        (new SchemaInitializer($pdo, __DIR__ . '/../../../database/schema.sql'))->initialize();

        return new ApplicationIntegrationContext(
            accounts: new SQLiteAccountRepository($pdo),
            ledgerEntries: new SQLiteLedgerEntryRepository($pdo),
            idempotency: new SQLiteIdempotencyRepository($pdo),
            transactions: new PdoTransactionManager($pdo),
            ids: new SequentialIdentifierGenerator(),
            clock: new FixedClock(new DateTimeImmutable('2026-06-26T12:00:00+00:00')),
        );
    }

    private function createAccount(ApplicationIntegrationContext $context, string $accountId): void
    {
        (new CreateAccount($context->accounts, $context->transactions))(
            new CreateAccountInput(accountId: $accountId, currency: 'ARS'),
        );
    }

    private function depositFunds(ApplicationIntegrationContext $context): DepositFunds
    {
        return new DepositFunds(
            accounts: $context->accounts,
            ledgerEntries: $context->ledgerEntries,
            idempotency: $context->idempotency,
            transactionManager: $context->transactions,
            financialLedger: new FinancialLedger(),
            identifierGenerator: $context->ids,
            clock: $context->clock,
        );
    }

    private function withdrawFunds(ApplicationIntegrationContext $context): WithdrawFunds
    {
        return new WithdrawFunds(
            accounts: $context->accounts,
            ledgerEntries: $context->ledgerEntries,
            idempotency: $context->idempotency,
            transactionManager: $context->transactions,
            financialLedger: new FinancialLedger(),
            identifierGenerator: $context->ids,
            clock: $context->clock,
        );
    }

    private function transferFunds(ApplicationIntegrationContext $context): TransferFunds
    {
        return new TransferFunds(
            accounts: $context->accounts,
            ledgerEntries: $context->ledgerEntries,
            idempotency: $context->idempotency,
            transactionManager: $context->transactions,
            financialLedger: new FinancialLedger(),
            identifierGenerator: $context->ids,
            clock: $context->clock,
        );
    }
}
