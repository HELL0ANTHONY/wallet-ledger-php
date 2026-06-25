<?php

declare(strict_types=1);

namespace WalletLedger\Application\Account\UseCase;

use WalletLedger\Application\Account\DTO\AccountOutput;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Account\Repository\AccountRepository;
use WalletLedger\Application\Shared\Transaction\TransactionManager;
use WalletLedger\Domain\Account\Account;
use WalletLedger\Domain\Account\AccountId;
use WalletLedger\Domain\Money\Currency;

final readonly class CreateAccount
{
    public function __construct(
        private AccountRepository $accounts,
        private TransactionManager $transactionManager,
    ) {}

    public function __invoke(CreateAccountInput $input): AccountOutput
    {
        return $this->transactionManager->transactional(function () use ($input): AccountOutput {
            $account = Account::open(
                id: new AccountId($input->accountId),
                currency: new Currency($input->currency),
            );

            $this->accounts->save($account);

            return new AccountOutput(
                accountId: $account->id->value,
                balance: $account->balance()->amount,
                currency: $account->currency->code,
            );
        });
    }
}
