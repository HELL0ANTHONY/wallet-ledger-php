<?php

declare(strict_types=1);

namespace WalletLedger\Application\Account\DTO;

final readonly class GetAccountBalanceInput
{
    public function __construct(
        public string $accountId,
    ) {}
}
