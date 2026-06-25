<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class LedgerEntryOutput
{
    public function __construct(
        public string $ledgerEntryId,
        public string $accountId,
        public string $operationId,
        public string $type,
        public int $amount,
        public string $currency,
        public int $balanceAfter,
        public string $createdAt,
    ) {}
}
