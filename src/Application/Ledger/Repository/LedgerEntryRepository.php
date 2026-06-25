<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\Repository;

use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Ledger\LedgerEntry;

interface LedgerEntryRepository
{
    public function append(LedgerEntry $entry): void;

    /**
     * @return list<LedgerEntry>
     */
    public function listByAccount(AccountId $accountId): array;
}
