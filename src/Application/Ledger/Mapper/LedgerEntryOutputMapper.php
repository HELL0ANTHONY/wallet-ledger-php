<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\Mapper;

use DateTimeInterface;
use WalletLedger\Application\Ledger\DTO\LedgerEntryOutput;
use WalletLedger\Domain\Ledger\LedgerEntry;

final readonly class LedgerEntryOutputMapper
{
    public function map(LedgerEntry $entry): LedgerEntryOutput
    {
        return new LedgerEntryOutput(
            ledgerEntryId: $entry->id->value,
            accountId: $entry->accountId->value,
            operationId: $entry->operationId->value,
            type: $entry->type->value,
            amount: $entry->amount->amount,
            currency: $entry->amount->currency->code,
            balanceAfter: $entry->balanceAfter->amount,
            createdAt: $entry->createdAt->format(DateTimeInterface::ATOM),
        );
    }
}
