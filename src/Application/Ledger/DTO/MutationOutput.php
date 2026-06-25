<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class MutationOutput
{
    /**
     * @param list<LedgerEntryOutput> $ledgerEntries
     */
    public function __construct(
        public string $operationId,
        public int $balance,
        public string $currency,
        public array $ledgerEntries,
    ) {}
}
