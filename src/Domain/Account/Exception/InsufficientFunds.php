<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Account\Exception;

use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Shared\Exception\DomainException;

final class InsufficientFunds extends DomainException
{
    public static function forAccount(AccountId $accountId): self
    {
        return new self("Insufficient funds for account: {$accountId->value}");
    }
}
