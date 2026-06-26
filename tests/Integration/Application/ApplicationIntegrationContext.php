<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Integration\Application;

use WalletLedger\Infrastructure\Database\PdoTransactionManager;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteAccountRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteIdempotencyRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteLedgerEntryRepository;
use WalletLedger\Tests\Unit\Application\Support\FixedClock;
use WalletLedger\Tests\Unit\Application\Support\SequentialIdentifierGenerator;

final readonly class ApplicationIntegrationContext
{
    public function __construct(
        public SQLiteAccountRepository $accounts,
        public SQLiteLedgerEntryRepository $ledgerEntries,
        public SQLiteIdempotencyRepository $idempotency,
        public PdoTransactionManager $transactions,
        public SequentialIdentifierGenerator $ids,
        public FixedClock $clock,
    ) {}
}
