<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Http;

use Override;
use PHPUnit\Framework\TestCase;
use Slim\App;
use WalletLedger\Tests\Http\Support\TestApp;

final class LedgerHttpTest extends TestCase
{
    /** @var App<null> */
    private App $app;

    private string $accountId;

    #[Override]
    protected function setUp(): void
    {
        $this->app = TestApp::create();
        $this->accountId = TestApp::accountData(
            TestApp::request($this->app, 'POST', '/accounts', ['currency' => 'ARS']),
        )['account_id'];
    }

    public function test_it_deposits_funds(): void
    {
        $response = TestApp::request(
            $this->app,
            'POST',
            "/accounts/{$this->accountId}/deposits",
            ['amount' => 10000, 'currency' => 'ARS'],
            ['Idempotency-Key' => 'key-001'],
        );

        self::assertSame(201, $response->getStatusCode());
        $data = TestApp::mutationData($response);
        self::assertSame(10000, $data['balance']);
        self::assertSame('ARS', $data['currency']);
        self::assertCount(1, $data['ledger_entries']);
        self::assertSame('credit', $data['ledger_entries'][0]['type']);
    }

    public function test_it_returns_400_without_idempotency_key_header(): void
    {
        $response = TestApp::request(
            $this->app,
            'POST',
            "/accounts/{$this->accountId}/deposits",
            ['amount' => 10000, 'currency' => 'ARS'],
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('bad_request', TestApp::errorData($response)['code']);
    }

    public function test_it_returns_same_response_for_duplicate_idempotency_key(): void
    {
        $payload = ['amount' => 5000, 'currency' => 'ARS'];
        $headers = ['Idempotency-Key' => 'key-idem-001'];

        $first = TestApp::mutationData(TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", $payload, $headers));
        $second = TestApp::mutationData(TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", $payload, $headers));

        self::assertSame($first['operation_id'], $second['operation_id']);
        self::assertSame(5000, $second['balance']);
    }

    public function test_it_withdraws_funds(): void
    {
        TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", ['amount' => 10000, 'currency' => 'ARS'], ['Idempotency-Key' => 'dep-001']);

        $response = TestApp::request(
            $this->app,
            'POST',
            "/accounts/{$this->accountId}/withdrawals",
            ['amount' => 3000, 'currency' => 'ARS'],
            ['Idempotency-Key' => 'wdl-001'],
        );

        self::assertSame(201, $response->getStatusCode());
        $data = TestApp::mutationData($response);
        self::assertSame(7000, $data['balance']);
        self::assertSame('debit', $data['ledger_entries'][0]['type']);
    }

    public function test_it_returns_422_for_insufficient_funds(): void
    {
        $response = TestApp::request(
            $this->app,
            'POST',
            "/accounts/{$this->accountId}/withdrawals",
            ['amount' => 5000, 'currency' => 'ARS'],
            ['Idempotency-Key' => 'wdl-001'],
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('business_rule_violation', TestApp::errorData($response)['code']);
    }

    public function test_it_returns_409_for_idempotency_key_reuse_with_different_payload(): void
    {
        TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", ['amount' => 10000, 'currency' => 'ARS'], ['Idempotency-Key' => 'dep-001']);
        TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", ['amount' => 5000, 'currency' => 'ARS'], ['Idempotency-Key' => 'same-key']);

        $response = TestApp::request(
            $this->app,
            'POST',
            "/accounts/{$this->accountId}/deposits",
            ['amount' => 99999, 'currency' => 'ARS'],
            ['Idempotency-Key' => 'same-key'],
        );

        self::assertSame(409, $response->getStatusCode());
        self::assertSame('conflict', TestApp::errorData($response)['code']);
    }

    public function test_it_transfers_funds_between_accounts(): void
    {
        $toAccountId = TestApp::accountData(
            TestApp::request($this->app, 'POST', '/accounts', ['currency' => 'ARS']),
        )['account_id'];

        TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", ['amount' => 20000, 'currency' => 'ARS'], ['Idempotency-Key' => 'dep-001']);

        $response = TestApp::request(
            $this->app,
            'POST',
            '/transfers',
            [
                'from_account_id' => $this->accountId,
                'to_account_id' => $toAccountId,
                'amount' => 8000,
                'currency' => 'ARS',
            ],
            ['Idempotency-Key' => 'trn-001'],
        );

        self::assertSame(201, $response->getStatusCode());
        $data = TestApp::mutationData($response);
        self::assertSame(12000, $data['balance']);
        self::assertCount(2, $data['ledger_entries']);
        self::assertSame('debit', $data['ledger_entries'][0]['type']);
        self::assertSame('credit', $data['ledger_entries'][1]['type']);
    }

    public function test_it_returns_422_for_same_account_transfer(): void
    {
        $response = TestApp::request(
            $this->app,
            'POST',
            '/transfers',
            [
                'from_account_id' => $this->accountId,
                'to_account_id' => $this->accountId,
                'amount' => 1000,
                'currency' => 'ARS',
            ],
            ['Idempotency-Key' => 'trn-001'],
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('business_rule_violation', TestApp::errorData($response)['code']);
    }

    public function test_it_lists_ledger_entries(): void
    {
        TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", ['amount' => 5000, 'currency' => 'ARS'], ['Idempotency-Key' => 'dep-001']);
        TestApp::request($this->app, 'POST', "/accounts/{$this->accountId}/deposits", ['amount' => 3000, 'currency' => 'ARS'], ['Idempotency-Key' => 'dep-002']);

        $response = TestApp::request($this->app, 'GET', "/accounts/{$this->accountId}/transactions");

        self::assertSame(200, $response->getStatusCode());
        $data = TestApp::ledgerData($response);
        self::assertCount(2, $data['entries']);
        self::assertSame('credit', $data['entries'][0]['type']);
        self::assertSame('credit', $data['entries'][1]['type']);
    }
}
