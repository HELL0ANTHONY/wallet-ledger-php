<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Domain\Ledger;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Ledger\IdempotencyKey;
use WalletLedger\Domain\Ledger\LedgerEntry;
use WalletLedger\Domain\Ledger\LedgerEntryId;
use WalletLedger\Domain\Ledger\LedgerEntryType;
use WalletLedger\Domain\Ledger\OperationId;
use WalletLedger\Domain\Money\Currency;
use WalletLedger\Domain\Money\Money;
use WalletLedger\Domain\Shared\Exception\InvalidIdentifier;

final class LedgerEntryTest extends TestCase
{
    public function test_it_represents_a_ledger_entry(): void
    {
        $createdAt = new DateTimeImmutable('2026-06-25T12:00:00+00:00');
        $entry = new LedgerEntry(
            id: new LedgerEntryId('led_123'),
            accountId: new AccountId('acc_123'),
            operationId: new OperationId('op_123'),
            type: LedgerEntryType::Credit,
            amount: Money::positive(15000, new Currency('ARS')),
            balanceAfter: Money::positive(15000, new Currency('ARS')),
            createdAt: $createdAt,
        );

        self::assertSame('led_123', $entry->id->value);
        self::assertSame('acc_123', $entry->accountId->value);
        self::assertSame('op_123', $entry->operationId->value);
        self::assertSame(LedgerEntryType::Credit, $entry->type);
        self::assertSame(15000, $entry->amount->amount);
        self::assertSame($createdAt, $entry->createdAt);
    }

    public function test_it_rejects_invalid_identifiers(): void
    {
        $this->expectException(InvalidIdentifier::class);

        new AccountId('x');
    }

    public function test_it_accepts_idempotency_key_as_identifier(): void
    {
        $key = new IdempotencyKey('transfer-001');

        self::assertSame('transfer-001', $key->value);
    }
}
