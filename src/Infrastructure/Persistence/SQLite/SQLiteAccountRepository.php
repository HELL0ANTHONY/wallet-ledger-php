<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Persistence\SQLite;

use DateTimeImmutable;
use Override;
use PDO;
use RuntimeException;
use WalletLedger\Application\Account\Repository\AccountRepository;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Money;

use function is_array;

final readonly class SQLiteAccountRepository implements AccountRepository
{
    public function __construct(
        private PDO $pdo,
        private SQLiteRowReader $rowReader = new SQLiteRowReader(),
    ) {}

    #[Override]
    public function get(AccountId $accountId): Account
    {
        $statement = $this->pdo->prepare('SELECT id, currency, balance FROM accounts WHERE id = :id');
        $statement->execute(['id' => $accountId->value]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            throw new RuntimeException("Account not found: {$accountId->value}");
        }

        $currency = new Currency($this->rowReader->string($row, 'currency'));

        return Account::restore(
            id: new AccountId($this->rowReader->string($row, 'id')),
            currency: $currency,
            balance: $this->moneyFromStoredAmount($this->rowReader->int($row, 'balance'), $currency),
        );
    }

    #[Override]
    public function save(Account $account): void
    {
        $now = new DateTimeImmutable();

        $statement = $this->pdo->prepare(
            <<<'SQL'
                INSERT INTO accounts (id, currency, balance, created_at, updated_at)
                VALUES (:id, :currency, :balance, :created_at, :updated_at)
                ON CONFLICT(id) DO UPDATE SET
                    currency = excluded.currency,
                    balance = excluded.balance,
                    updated_at = excluded.updated_at
                SQL,
        );

        $statement->execute([
            'id' => $account->id->value,
            'currency' => $account->currency->code,
            'balance' => $account->balance()->amount,
            'created_at' => $now->format(DATE_ATOM),
            'updated_at' => $now->format(DATE_ATOM),
        ]);
    }

    private function moneyFromStoredAmount(int $amount, Currency $currency): Money
    {
        if ($amount === 0) {
            return Money::zero($currency);
        }

        return Money::positive($amount, $currency);
    }
}
