<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Ledger;

use PHPUnit\Framework\TestCase;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Ledger\DTO\DepositFundsInput;
use WalletLedger\Application\Ledger\DTO\ListAccountLedgerEntriesInput;
use WalletLedger\Application\Ledger\DTO\TransferFundsInput;
use WalletLedger\Application\Ledger\DTO\WithdrawFundsInput;
use WalletLedger\Application\Ledger\Exception\IdempotencyConflict;
use WalletLedger\Application\Ledger\UseCase\DepositFunds;
use WalletLedger\Application\Ledger\UseCase\ListAccountLedgerEntries;
use WalletLedger\Application\Ledger\UseCase\TransferFunds;
use WalletLedger\Application\Ledger\UseCase\WithdrawFunds;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Tests\Unit\Application\Support\FixedClock;
use WalletLedger\Tests\Unit\Application\Support\InMemoryAccountRepository;
use WalletLedger\Tests\Unit\Application\Support\InMemoryIdempotencyRepository;
use WalletLedger\Tests\Unit\Application\Support\InMemoryLedgerEntryRepository;
use WalletLedger\Tests\Unit\Application\Support\RecordingTransactionManager;
use WalletLedger\Tests\Unit\Application\Support\SequentialIdentifierGenerator;

final class LedgerUseCasesTest extends TestCase
{
    public function test_it_deposits_funds_idempotently(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123', 'ARS');

        $useCase = $this->depositFunds($context);
        $input = new DepositFundsInput(
            accountId: 'acc_123',
            amount: 15000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        );

        $first = $useCase($input);
        $second = $useCase($input);

        self::assertSame('op_1', $first->operationId);
        self::assertSame(15000, $first->balance);
        self::assertSame($first->operationId, $second->operationId);
        self::assertSame(1, $context->ledgerEntries->count());
        self::assertSame(3, $context->transactions->transactions);
    }

    public function test_it_rejects_idempotency_key_reuse_with_different_payload(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123', 'ARS');
        $useCase = $this->depositFunds($context);

        $useCase(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 15000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));

        $this->expectException(IdempotencyConflict::class);

        $useCase(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 20000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));
    }

    public function test_it_withdraws_funds(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123', 'ARS');
        $this->depositFunds($context)(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 15000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));

        $output = $this->withdrawFunds($context)(new WithdrawFundsInput(
            accountId: 'acc_123',
            amount: 5000,
            currency: 'ARS',
            idempotencyKey: 'withdrawal-001',
        ));

        self::assertSame(10000, $output->balance);
        self::assertSame('debit', $output->ledgerEntries[0]->type);
        self::assertSame(2, $context->ledgerEntries->count());
    }

    public function test_it_transfers_funds_and_lists_account_ledger_entries(): void
    {
        $context = $this->context();
        $this->createAccount($context, 'acc_123', 'ARS');
        $this->createAccount($context, 'acc_456', 'ARS');
        $this->depositFunds($context)(new DepositFundsInput(
            accountId: 'acc_123',
            amount: 20000,
            currency: 'ARS',
            idempotencyKey: 'deposit-001',
        ));

        $transfer = $this->transferFunds($context)(new TransferFundsInput(
            fromAccountId: 'acc_123',
            toAccountId: 'acc_456',
            amount: 15000,
            currency: 'ARS',
            idempotencyKey: 'transfer-001',
        ));
        $fromEntries = (new ListAccountLedgerEntries($context->ledgerEntries))(
            new ListAccountLedgerEntriesInput(accountId: 'acc_123'),
        );
        $toEntries = (new ListAccountLedgerEntries($context->ledgerEntries))(
            new ListAccountLedgerEntriesInput(accountId: 'acc_456'),
        );

        self::assertSame(5000, $transfer->balance);
        self::assertCount(2, $transfer->ledgerEntries);
        self::assertSame($transfer->operationId, $transfer->ledgerEntries[0]->operationId);
        self::assertSame($transfer->operationId, $transfer->ledgerEntries[1]->operationId);
        self::assertCount(2, $fromEntries->entries);
        self::assertCount(1, $toEntries->entries);
        self::assertSame('credit', $toEntries->entries[0]->type);
    }

    private function context(): LedgerUseCaseContext
    {
        return new LedgerUseCaseContext(
            accounts: new InMemoryAccountRepository(),
            ledgerEntries: new InMemoryLedgerEntryRepository(),
            idempotency: new InMemoryIdempotencyRepository(),
            transactions: new RecordingTransactionManager(),
            ids: new SequentialIdentifierGenerator(),
            clock: new FixedClock(),
        );
    }

    private function createAccount(LedgerUseCaseContext $context, string $accountId, string $currency): void
    {
        (new CreateAccount($context->accounts, $context->transactions))(
            new CreateAccountInput(accountId: $accountId, currency: $currency),
        );
    }

    private function depositFunds(LedgerUseCaseContext $context): DepositFunds
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

    private function withdrawFunds(LedgerUseCaseContext $context): WithdrawFunds
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

    private function transferFunds(LedgerUseCaseContext $context): TransferFunds
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
