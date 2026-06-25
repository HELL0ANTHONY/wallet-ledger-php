<?php

declare(strict_types=1);

namespace WalletLedger\Application\Account\DTO;

final readonly class AccountOutput
{
    public function __construct(
        public string $accountId,
        public int $balance,
        public string $currency,
    ) {}
}
