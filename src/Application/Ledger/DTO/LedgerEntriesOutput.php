<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class LedgerEntriesOutput
{
    /**
     * @param list<LedgerEntryOutput> $entries
     */
    public function __construct(
        public array $entries,
    ) {}
}
