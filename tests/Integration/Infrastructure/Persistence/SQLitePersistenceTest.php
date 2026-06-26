<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Integration\Infrastructure\Persistence;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Ledger\DTO\DepositFundsInput;
use WalletLedger\Application\Ledger\DTO\ListAccountLedgerEntriesInput;
use WalletLedger\Application\Ledger\UseCase\DepositFunds;
use WalletLedger\Application\Ledger\UseCase\ListAccountLedgerEntries;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Account\Exception\AccountNotFound;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Infrastructure\Config\DatabaseConfig;
use WalletLedger\Infrastructure\Database\PdoConnectionFactory;
use WalletLedger\Infrastructure\Database\PdoTransactionManager;
use WalletLedger\Infrastructure\Database\SchemaInitializer;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteAccountRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteIdempotencyRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteLedgerEntryRepository;
use WalletLedger\Tests\Unit\Application\Support\FixedClock;
use WalletLedger\Tests\Unit\Application\Support\SequentialIdentifierGenerator;

final class SQLitePersistenceTest extends TestCase
{
    public function test_it_persists_accounts_ledger_entries_and_idempotency(): void
    {
        $context = $this->context();

        (new CreateAccount($context->accounts, $context->transactions))(
            new CreateAccountInput(accountId: 'acc_123', currency: 'ARS'),
        );

        $deposit = new DepositFunds(
            accounts: $context->accounts,
            ledgerEntries: $context->ledgerEntries,
            idempotency: $context->idempotency,
            transactionManager: $context->transactions,
            financialLedger: new FinancialLedger(),
            identifierGenerator: new SequentialIdentifierGenerator(),
            clock: new FixedClock(new DateTimeImmutable('2026-06-25T12:00:00+00:00')),
        );

        $first = $deposit(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 15000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));
        $second = $deposit(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 15000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));

        $entries = (new ListAccountLedgerEntries($context->ledgerEntries))(
            new ListAccountLedgerEntriesInput(accountId: 'acc_123'),
        );
        $account = $context->accounts->get(new AccountId('acc_123'));

        self::assertSame(15000, $account->balance()->amount);
        self::assertSame($first->operationId, $second->operationId);
        self::assertCount(1, $entries->entries);
        self::assertSame('credit', $entries->entries[0]->type);
    }

    public function test_transaction_manager_rolls_back_failed_operations(): void
    {
        $context = $this->context();

        try {
            $context->transactions->transactional(function () use ($context): never {
                $context->accounts->save(Account::open(new AccountId('acc_123'), new Currency('ARS')));

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        $this->expectException(AccountNotFound::class);
        $this->expectExceptionMessage('Account not found: acc_123');

        $context->accounts->get(new AccountId('acc_123'));
    }

    private function context(): SQLitePersistenceContext
    {
        $pdo = (new PdoConnectionFactory(new DatabaseConfig('sqlite::memory:', ':memory:')))->create();
        (new SchemaInitializer($pdo, __DIR__ . '/../../../../database/schema.sql'))->initialize();

        return new SQLitePersistenceContext(
            accounts: new SQLiteAccountRepository($pdo),
            ledgerEntries: new SQLiteLedgerEntryRepository($pdo),
            idempotency: new SQLiteIdempotencyRepository($pdo),
            transactions: new PdoTransactionManager($pdo),
        );
    }
}
