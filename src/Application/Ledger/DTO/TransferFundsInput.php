<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class TransferFundsInput
{
    public function __construct(
        public string $fromAccountId,
        public string $toAccountId,
        public int $amount,
        public string $currency,
        public string $idempotencyKey,
    ) {}
}
