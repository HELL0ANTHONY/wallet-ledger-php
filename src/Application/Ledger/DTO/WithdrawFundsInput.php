<?php

declare(strict_types=1);

namespace WalletLedger\Application\Ledger\DTO;

final readonly class WithdrawFundsInput
{
    public function __construct(
        public string $accountId,
        public int $amount,
        public string $currency,
        public string $idempotencyKey,
    ) {}
}
