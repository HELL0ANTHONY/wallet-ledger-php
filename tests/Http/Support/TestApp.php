<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Http\Support;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Throwable;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Account\UseCase\GetAccountBalance;
use WalletLedger\Application\Ledger\Exception\IdempotencyConflict;
use WalletLedger\Application\Ledger\UseCase\DepositFunds;
use WalletLedger\Application\Ledger\UseCase\ListAccountLedgerEntries;
use WalletLedger\Application\Ledger\UseCase\TransferFunds;
use WalletLedger\Application\Ledger\UseCase\WithdrawFunds;
use WalletLedger\Domain\Account\Exception\AccountNotFound;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Domain\Shared\Exception\DomainException;
use WalletLedger\Http\Controller\AccountController;
use WalletLedger\Http\Controller\LedgerController;
use WalletLedger\Http\Response\JsonResponder;
use WalletLedger\Infrastructure\Clock\SystemClock;
use WalletLedger\Infrastructure\Config\DatabaseConfig;
use WalletLedger\Infrastructure\Database\PdoConnectionFactory;
use WalletLedger\Infrastructure\Database\PdoTransactionManager;
use WalletLedger\Infrastructure\Database\SchemaInitializer;
use WalletLedger\Infrastructure\Id\RandomIdentifierGenerator;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteAccountRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteIdempotencyRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteLedgerEntryRepository;

use function is_array;
use function is_callable;
use function json_decode;
use function json_encode;

final class TestApp
{
    /**
     * @return App<null>
     */
    public static function create(): App
    {
        $pdo = (new PdoConnectionFactory(new DatabaseConfig('sqlite::memory:', ':memory:')))->create();
        (new SchemaInitializer($pdo, __DIR__ . '/../../../database/schema.sql'))->initialize();

        $accounts = new SQLiteAccountRepository($pdo);
        $ledgerEntries = new SQLiteLedgerEntryRepository($pdo);
        $idempotency = new SQLiteIdempotencyRepository($pdo);
        $transactions = new PdoTransactionManager($pdo);
        $financialLedger = new FinancialLedger();
        $ids = new RandomIdentifierGenerator();
        $clock = new SystemClock();

        $accountController = new AccountController(
            createAccount: new CreateAccount($accounts, $transactions),
            getAccountBalance: new GetAccountBalance($accounts),
            identifierGenerator: $ids,
        );
        $ledgerController = new LedgerController(
            depositFunds: new DepositFunds($accounts, $ledgerEntries, $idempotency, $transactions, $financialLedger, $ids, $clock),
            withdrawFunds: new WithdrawFunds($accounts, $ledgerEntries, $idempotency, $transactions, $financialLedger, $ids, $clock),
            transferFunds: new TransferFunds($accounts, $ledgerEntries, $idempotency, $transactions, $financialLedger, $ids, $clock),
            listAccountLedgerEntries: new ListAccountLedgerEntries($ledgerEntries),
        );

        /** @var App<null> $app */
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $responder = new JsonResponder();
        $responseFactory = $app->getResponseFactory();
        $errorMiddleware = $app->addErrorMiddleware(false, false, false);
        $errorMiddleware->setDefaultErrorHandler(
            static function (
                ServerRequestInterface $request,
                Throwable $exception,
                bool $displayErrorDetails,
                bool $logErrors,
                bool $logErrorDetails,
            ) use ($responseFactory, $responder): ResponseInterface {
                unset($request, $displayErrorDetails, $logErrors, $logErrorDetails);

                $status = match (true) {
                    $exception instanceof InvalidArgumentException,
                    $exception instanceof JsonException => 400,
                    $exception instanceof AccountNotFound => 404,
                    $exception instanceof IdempotencyConflict => 409,
                    $exception instanceof DomainException => 422,
                    default => 500,
                };

                $code = match ($status) {
                    400 => 'bad_request',
                    404 => 'not_found',
                    409 => 'conflict',
                    422 => 'business_rule_violation',
                    default => 'internal_server_error',
                };

                return $responder->respond($responseFactory->createResponse(), [
                    'error' => [
                        'code' => $code,
                        'message' => $status === 500 ? 'Unexpected server error.' : $exception->getMessage(),
                    ],
                ], $status);
            },
        );

        $registerRoutes = require __DIR__ . '/../../../config/routes.php';
        if (!is_callable($registerRoutes)) {
            throw new RuntimeException('Routes file must return a callable.');
        }

        $registerRoutes($app, $accountController, $ledgerController);

        return $app;
    }

    /**
     * @param App<null> $app
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    public static function request(
        App $app,
        string $method,
        string $uri,
        array $body = [],
        array $headers = [],
    ): ResponseInterface {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($body !== []) {
            $stream = (new StreamFactory())->createStream(json_encode($body, JSON_THROW_ON_ERROR));
            $request = $request
                ->withBody($stream)
                ->withHeader('Content-Type', 'application/json');
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $app->handle($request);
    }

    /**
     * @return array{account_id: string, balance: int, currency: string}
     */
    public static function accountData(ResponseInterface $response): array
    {
        $body = self::parseBody($response);
        if (!is_array($body['data'] ?? null)) {
            throw new RuntimeException('Response has no data object.');
        }

        /** @var array{account_id: string, balance: int, currency: string} $data */
        $data = $body['data'];

        return $data;
    }

    /**
     * @return array{operation_id: string, balance: int, currency: string, ledger_entries: list<array{type: string, ledger_entry_id: string, operation_id: string}>}
     */
    public static function mutationData(ResponseInterface $response): array
    {
        $body = self::parseBody($response);
        if (!is_array($body['data'] ?? null)) {
            throw new RuntimeException('Response has no data object.');
        }

        /** @var array{operation_id: string, balance: int, currency: string, ledger_entries: list<array{type: string, ledger_entry_id: string, operation_id: string}>} $data */
        $data = $body['data'];

        return $data;
    }

    /**
     * @return array{entries: list<array{type: string}>}
     */
    public static function ledgerData(ResponseInterface $response): array
    {
        $body = self::parseBody($response);
        if (!is_array($body['data'] ?? null)) {
            throw new RuntimeException('Response has no data object.');
        }

        /** @var array{entries: list<array{type: string}>} $data */
        $data = $body['data'];

        return $data;
    }

    /**
     * @return array{code: string, message: string}
     */
    public static function errorData(ResponseInterface $response): array
    {
        $body = self::parseBody($response);
        if (!is_array($body['error'] ?? null)) {
            throw new RuntimeException('Response has no error object.');
        }

        /** @var array{code: string, message: string} $error */
        $error = $body['error'];

        return $error;
    }

    /**
     * @return array<string, mixed>
     */
    private static function parseBody(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Response body is not a JSON object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
