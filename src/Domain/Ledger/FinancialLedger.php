<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Ledger;

use DateTimeImmutable;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\Exception\SameAccountTransfer;
use WalletLedger\Domain\Money\Exception\CurrencyMismatch;
use WalletLedger\Domain\Money\Money;

final readonly class FinancialLedger
{
    public function deposit(
        Account $account,
        LedgerEntryId $entryId,
        OperationId $operationId,
        Money $amount,
        DateTimeImmutable $createdAt,
    ): LedgerEntry {
        $this->assertAccountCurrencyMatches($account, $amount);

        $balanceAfter = $account->deposit($amount);

        return new LedgerEntry(
            id: $entryId,
            accountId: $account->id,
            operationId: $operationId,
            type: LedgerEntryType::Credit,
            amount: $amount,
            balanceAfter: $balanceAfter,
            createdAt: $createdAt,
        );
    }

    public function withdraw(
        Account $account,
        LedgerEntryId $entryId,
        OperationId $operationId,
        Money $amount,
        DateTimeImmutable $createdAt,
    ): LedgerEntry {
        $this->assertAccountCurrencyMatches($account, $amount);

        $balanceAfter = $account->withdraw($amount);

        return new LedgerEntry(
            id: $entryId,
            accountId: $account->id,
            operationId: $operationId,
            type: LedgerEntryType::Debit,
            amount: $amount,
            balanceAfter: $balanceAfter,
            createdAt: $createdAt,
        );
    }

    public function transfer(
        Account $fromAccount,
        Account $toAccount,
        LedgerEntryId $debitEntryId,
        LedgerEntryId $creditEntryId,
        OperationId $operationId,
        Money $amount,
        DateTimeImmutable $createdAt,
    ): TransferLedgerEntries {
        if ($fromAccount->id->equals($toAccount->id)) {
            throw SameAccountTransfer::forAccount($fromAccount->id);
        }

        $this->assertAccountCurrencyMatches($fromAccount, $amount);
        $this->assertAccountCurrencyMatches($toAccount, $amount);

        $fromBalanceAfter = $fromAccount->withdraw($amount);
        $toBalanceAfter = $toAccount->deposit($amount);

        return new TransferLedgerEntries(
            debit: new LedgerEntry(
                id: $debitEntryId,
                accountId: $fromAccount->id,
                operationId: $operationId,
                type: LedgerEntryType::Debit,
                amount: $amount,
                balanceAfter: $fromBalanceAfter,
                createdAt: $createdAt,
            ),
            credit: new LedgerEntry(
                id: $creditEntryId,
                accountId: $toAccount->id,
                operationId: $operationId,
                type: LedgerEntryType::Credit,
                amount: $amount,
                balanceAfter: $toBalanceAfter,
                createdAt: $createdAt,
            ),
        );
    }

    private function assertAccountCurrencyMatches(Account $account, Money $amount): void
    {
        if (!$account->currency->equals($amount->currency)) {
            throw CurrencyMismatch::between($account->currency, $amount->currency);
        }
    }
}
