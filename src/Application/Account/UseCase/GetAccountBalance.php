<?php

declare(strict_types=1);

namespace WalletLedger\Application\Account\UseCase;

use WalletLedger\Application\Account\DTO\AccountOutput;
use WalletLedger\Application\Account\DTO\GetAccountBalanceInput;
use WalletLedger\Application\Account\Repository\AccountRepository;
use WalletLedger\Domain\Account\AccountId;

final readonly class GetAccountBalance
{
    public function __construct(
        private AccountRepository $accounts,
    ) {}

    public function __invoke(GetAccountBalanceInput $input): AccountOutput
    {
        $account = $this->accounts->get(new AccountId($input->accountId));

        return new AccountOutput(
            accountId: $account->id->value,
            balance: $account->balance()->amount,
            currency: $account->currency->code,
        );
    }
}
