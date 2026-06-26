<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Http;

use Override;
use PHPUnit\Framework\TestCase;
use Slim\App;
use WalletLedger\Tests\Http\Support\TestApp;

final class AccountHttpTest extends TestCase
{
    /** @var App<null> */
    private App $app;

    #[Override]
    protected function setUp(): void
    {
        $this->app = TestApp::create();
    }

    public function test_it_creates_an_account(): void
    {
        $response = TestApp::request($this->app, 'POST', '/accounts', ['currency' => 'ARS']);

        self::assertSame(201, $response->getStatusCode());
        $data = TestApp::accountData($response);
        self::assertStringStartsWith('acc_', $data['account_id']);
        self::assertSame(0, $data['balance']);
        self::assertSame('ARS', $data['currency']);
    }

    public function test_it_gets_account_balance(): void
    {
        $created = TestApp::accountData(TestApp::request($this->app, 'POST', '/accounts', ['currency' => 'USD']));

        $response = TestApp::request($this->app, 'GET', "/accounts/{$created['account_id']}");

        self::assertSame(200, $response->getStatusCode());
        $data = TestApp::accountData($response);
        self::assertSame($created['account_id'], $data['account_id']);
        self::assertSame(0, $data['balance']);
        self::assertSame('USD', $data['currency']);
    }

    public function test_it_returns_404_for_unknown_account(): void
    {
        $response = TestApp::request($this->app, 'GET', '/accounts/acc_doesnotexist01');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('not_found', TestApp::errorData($response)['code']);
    }

    public function test_it_returns_400_for_missing_currency_field(): void
    {
        $response = TestApp::request($this->app, 'POST', '/accounts', []);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('bad_request', TestApp::errorData($response)['code']);
    }

    public function test_it_returns_422_for_invalid_currency_code(): void
    {
        $response = TestApp::request($this->app, 'POST', '/accounts', ['currency' => 'ars']);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('business_rule_violation', TestApp::errorData($response)['code']);
    }
}
