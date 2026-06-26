<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application\Account;

use PHPUnit\Framework\TestCase;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Account\DTO\GetAccountBalanceInput;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Account\UseCase\GetAccountBalance;
use WalletLedger\Domain\Account\Exception\AccountNotFound;
use WalletLedger\Tests\Unit\Application\Support\InMemoryAccountRepository;
use WalletLedger\Tests\Unit\Application\Support\RecordingTransactionManager;

final class AccountUseCasesTest extends TestCase
{
    public function test_it_creates_and_gets_account_balance(): void
    {
        $accounts = new InMemoryAccountRepository();
        $transactions = new RecordingTransactionManager();

        $created = (new CreateAccount($accounts, $transactions))(
            new CreateAccountInput(accountId: 'acc_123', currency: 'ARS'),
        );
        $balance = (new GetAccountBalance($accounts))(
            new GetAccountBalanceInput(accountId: 'acc_123'),
        );

        self::assertSame('acc_123', $created->accountId);
        self::assertSame(0, $created->balance);
        self::assertSame('ARS', $created->currency);
        self::assertSame($created->accountId, $balance->accountId);
        self::assertSame($created->balance, $balance->balance);
        self::assertSame(1, $transactions->transactions);
    }

    public function test_it_throws_when_getting_balance_of_unknown_account(): void
    {
        $accounts = new InMemoryAccountRepository();

        $this->expectException(AccountNotFound::class);

        (new GetAccountBalance($accounts))(new GetAccountBalanceInput(accountId: 'acc_unknown01'));
    }
}
