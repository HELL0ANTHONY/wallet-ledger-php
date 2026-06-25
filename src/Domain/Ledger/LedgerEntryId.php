<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Ledger;

use WalletLedger\Domain\Shared\ValueObject\StringIdentifier;

final readonly class LedgerEntryId extends StringIdentifier {}
