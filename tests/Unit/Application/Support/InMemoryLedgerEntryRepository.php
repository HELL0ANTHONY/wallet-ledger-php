<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Support;

use Override;
use WalletLedger\Application\Ledger\Repository\LedgerEntryRepository;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Ledger\LedgerEntry;

use function count;

final class InMemoryLedgerEntryRepository implements LedgerEntryRepository
{
    /**
     * @var list<LedgerEntry>
     */
    private array $entries = [];

    #[Override]
    public function append(LedgerEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return list<LedgerEntry>
     */
    #[Override]
    public function listByAccount(AccountId $accountId): array
    {
        $entries = [];

        foreach ($this->entries as $entry) {
            if ($entry->accountId->equals($accountId)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
