<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Integration\Infrastructure\Persistence;

use WalletLedger\Infrastructure\Database\PdoTransactionManager;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteAccountRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteIdempotencyRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteLedgerEntryRepository;

final readonly class SQLitePersistenceContext
{
    public function __construct(
        public SQLiteAccountRepository $accounts,
        public SQLiteLedgerEntryRepository $ledgerEntries,
        public SQLiteIdempotencyRepository $idempotency,
        public PdoTransactionManager $transactions,
    ) {}
}
