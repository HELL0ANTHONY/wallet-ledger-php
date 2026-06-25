<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Ledger;

use DateTimeImmutable;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Money\Money;

final readonly class LedgerEntry
{
    public function __construct(
        public LedgerEntryId $id,
        public AccountId $accountId,
        public OperationId $operationId,
        public LedgerEntryType $type,
        public Money $amount,
        public Money $balanceAfter,
        public DateTimeImmutable $createdAt,
    ) {}
}
