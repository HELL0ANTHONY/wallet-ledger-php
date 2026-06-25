<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Ledger;

enum LedgerEntryType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
