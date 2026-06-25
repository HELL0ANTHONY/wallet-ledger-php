<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Persistence\SQLite;

use DateTimeImmutable;
use Override;
use PDO;
use WalletLedger\Application\Ledger\Repository\LedgerEntryRepository;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Ledger\LedgerEntry;
use WalletLedger\Domain\Ledger\LedgerEntryId;
use WalletLedger\Domain\Ledger\LedgerEntryType;
use WalletLedger\Domain\Ledger\OperationId;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Money;

use function is_array;

final readonly class SQLiteLedgerEntryRepository implements LedgerEntryRepository
{
    public function __construct(
        private PDO $pdo,
        private SQLiteRowReader $rowReader = new SQLiteRowReader(),
    ) {}

    #[Override]
    public function append(LedgerEntry $entry): void
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
                INSERT INTO ledger_entries (
                    id,
                    account_id,
                    operation_id,
                    type,
                    amount,
                    currency,
                    balance_after,
                    created_at
                ) VALUES (
                    :id,
                    :account_id,
                    :operation_id,
                    :type,
                    :amount,
                    :currency,
                    :balance_after,
                    :created_at
                )
                SQL,
        );

        $statement->execute([
            'id' => $entry->id->value,
            'account_id' => $entry->accountId->value,
            'operation_id' => $entry->operationId->value,
            'type' => $entry->type->value,
            'amount' => $entry->amount->amount,
            'currency' => $entry->amount->currency->code,
            'balance_after' => $entry->balanceAfter->amount,
            'created_at' => $entry->createdAt->format(DATE_ATOM),
        ]);
    }

    /**
     * @return list<LedgerEntry>
     */
    #[Override]
    public function listByAccount(AccountId $accountId): array
    {
        $statement = $this->pdo->prepare(
            <<<'SQL'
                SELECT id, account_id, operation_id, type, amount, currency, balance_after, created_at
                FROM ledger_entries
                WHERE account_id = :account_id
                ORDER BY created_at ASC, id ASC
                SQL,
        );
        $statement->execute(['account_id' => $accountId->value]);

        $entries = [];
        while (($row = $statement->fetch()) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $currency = new Currency($this->rowReader->string($row, 'currency'));
            $entries[] = new LedgerEntry(
                id: new LedgerEntryId($this->rowReader->string($row, 'id')),
                accountId: new AccountId($this->rowReader->string($row, 'account_id')),
                operationId: new OperationId($this->rowReader->string($row, 'operation_id')),
                type: LedgerEntryType::from($this->rowReader->string($row, 'type')),
                amount: Money::positive($this->rowReader->int($row, 'amount'), $currency),
                balanceAfter: $this->moneyFromStoredAmount($this->rowReader->int($row, 'balance_after'), $currency),
                createdAt: new DateTimeImmutable($this->rowReader->string($row, 'created_at')),
            );
        }

        return $entries;
    }

    private function moneyFromStoredAmount(int $amount, Currency $currency): Money
    {
        if ($amount === 0) {
            return Money::zero($currency);
        }

        return Money::positive($amount, $currency);
    }
}
