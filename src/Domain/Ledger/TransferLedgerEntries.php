<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Ledger;

final readonly class TransferLedgerEntries
{
    public function __construct(
        public LedgerEntry $debit,
        public LedgerEntry $credit,
    ) {}
}
