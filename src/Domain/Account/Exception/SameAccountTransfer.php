<?php

declare(strict_types=1);

namespace WalletLedger\Domain\Account\Exception;

use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Shared\Exception\DomainException;

final class SameAccountTransfer extends DomainException
{
    public static function forAccount(AccountId $accountId): self
    {
        return new self("Cannot transfer funds to the same account: {$accountId->value}");
    }
}
