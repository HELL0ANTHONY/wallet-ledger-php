<?php

declare(strict_types=1);

namespace WalletLedger\Application\Account\Repository;

use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;

interface AccountRepository
{
    public function get(AccountId $accountId): Account;

    public function save(Account $account): void;
}
