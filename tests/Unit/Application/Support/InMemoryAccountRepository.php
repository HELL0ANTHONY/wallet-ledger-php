<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Support;

use Override;
use WalletLedger\Application\Account\Repository\AccountRepository;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Account\Exception\AccountNotFound;

final class InMemoryAccountRepository implements AccountRepository
{
    /**
     * @var array<string, Account>
     */
    private array $accounts = [];

    #[Override]
    public function get(AccountId $accountId): Account
    {
        return $this->accounts[$accountId->value]
            ?? throw AccountNotFound::forId($accountId);
    }

    #[Override]
    public function save(Account $account): void
    {
        $this->accounts[$account->id->value] = $account;
    }
}
