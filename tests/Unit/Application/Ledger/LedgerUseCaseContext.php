<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Ledger;

use WalletLedger\Tests\Unit\Application\Support\FixedClock;
use WalletLedger\Tests\Unit\Application\Support\InMemoryAccountRepository;
use WalletLedger\Tests\Unit\Application\Support\InMemoryIdempotencyRepository;
use WalletLedger\Tests\Unit\Application\Support\InMemoryLedgerEntryRepository;
use WalletLedger\Tests\Unit\Application\Support\RecordingTransactionManager;
use WalletLedger\Tests\Unit\Application\Support\SequentialIdentifierGenerator;

final readonly class LedgerUseCaseContext
{
    public function __construct(
        public InMemoryAccountRepository $accounts,
        public InMemoryLedgerEntryRepository $ledgerEntries,
        public InMemoryIdempotencyRepository $idempotency,
        public RecordingTransactionManager $transactions,
        public SequentialIdentifierGenerator $ids,
        public FixedClock $clock,
    ) {}
}
