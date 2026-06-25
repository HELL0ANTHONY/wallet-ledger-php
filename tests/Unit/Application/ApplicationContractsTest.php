<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use WalletLedger\Application\Account\DTO\AccountOutput;
use WalletLedger\Application\Account\DTO\CreateAccountInput;
use WalletLedger\Application\Ledger\DTO\LedgerEntryOutput;
use WalletLedger\Application\Ledger\DTO\MutationOutput;
use WalletLedger\Application\Ledger\DTO\StoredIdempotentResponse;

final class ApplicationContractsTest extends TestCase
{
    public function test_account_dtos_are_typed_boundaries(): void
    {
        $input = new CreateAccountInput(accountId: 'acc_123', currency: 'ARS');
        $output = new AccountOutput(accountId: 'acc_123', balance: 0, currency: 'ARS');

        self::assertSame('acc_123', $input->accountId);
        self::assertSame('ARS', $input->currency);
        self::assertSame(0, $output->balance);
    }

    public function test_mutation_output_contains_ledger_entries(): void
    {
        $entry = new LedgerEntryOutput(
            ledgerEntryId: 'led_123',
            accountId: 'acc_123',
            operationId: 'op_123',
            type: 'credit',
            amount: 15000,
            currency: 'ARS',
            balanceAfter: 15000,
            createdAt: '2026-06-25T12:00:00+00:00',
        );

        $output = new MutationOutput(
            operationId: 'op_123',
            balance: 15000,
            currency: 'ARS',
            ledgerEntries: [$entry],
        );

        self::assertSame('op_123', $output->operationId);
        self::assertSame($entry, $output->ledgerEntries[0]);
    }

    public function test_stored_idempotent_response_is_explicit(): void
    {
        $response = new StoredIdempotentResponse(
            responseCode: 201,
            responseBody: '{"status":"ok"}',
        );

        self::assertSame(201, $response->responseCode);
        self::assertSame('{"status":"ok"}', $response->responseBody);
    }
}
