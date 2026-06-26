<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use WalletLedger\Application\Account\UseCase\CreateAccount;
use WalletLedger\Application\Account\UseCase\GetAccountBalance;
use WalletLedger\Application\Ledger\Exception\IdempotencyConflict;
use WalletLedger\Application\Ledger\UseCase\DepositFunds;
use WalletLedger\Application\Ledger\UseCase\ListAccountLedgerEntries;
use WalletLedger\Application\Ledger\UseCase\TransferFunds;
use WalletLedger\Application\Ledger\UseCase\WithdrawFunds;
use WalletLedger\Domain\Ledger\FinancialLedger;
use WalletLedger\Domain\Shared\Exception\DomainException;
use WalletLedger\Http\Controller\AccountController;
use WalletLedger\Http\Controller\LedgerController;
use WalletLedger\Http\Response\JsonResponder;
use WalletLedger\Infrastructure\Clock\SystemClock;
use WalletLedger\Infrastructure\Config\AppEnvironment;
use WalletLedger\Infrastructure\Config\SettingsFactory;
use WalletLedger\Infrastructure\Database\PdoConnectionFactory;
use WalletLedger\Infrastructure\Database\PdoTransactionManager;
use WalletLedger\Infrastructure\Database\SchemaInitializer;
use WalletLedger\Infrastructure\Id\RandomIdentifierGenerator;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteAccountRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteIdempotencyRepository;
use WalletLedger\Infrastructure\Persistence\SQLite\SQLiteLedgerEntryRepository;

return static function (): Slim\App {
    $settings = (new SettingsFactory())->fromGlobals();
    $database = $settings->database->withProjectRoot(dirname(__DIR__));
    $pdo = (new PdoConnectionFactory($database))->create();
    (new SchemaInitializer($pdo, __DIR__ . '/../database/schema.sql'))->initialize();

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

    $app = AppFactory::create();
    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware(
        displayErrorDetails: $settings->app->environment === AppEnvironment::Local && $settings->app->debug,
        logErrors: true,
        logErrorDetails: true,
    );
    $errorMiddleware->setDefaultErrorHandler(errorHandler($app->getResponseFactory(), new JsonResponder()));

    $registerRoutes = require __DIR__ . '/routes.php';
    if (!is_callable($registerRoutes)) {
        throw new RuntimeException('Routes file must return a callable.');
    }

    $registerRoutes($app, $accountController, $ledgerController);

    return $app;
};

function errorHandler(
    Psr\Http\Message\ResponseFactoryInterface $responseFactory,
    JsonResponder $responder,
): callable {
    return static function (
        Psr\Http\Message\ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ) use ($responseFactory, $responder): ResponseInterface {
        unset($request, $displayErrorDetails, $logErrors, $logErrorDetails);

        $status = match (true) {
            $exception instanceof InvalidArgumentException,
            $exception instanceof JsonException => 400,
            $exception instanceof IdempotencyConflict => 409,
            $exception instanceof DomainException => 422,
            str_starts_with($exception->getMessage(), 'Account not found:') => 404,
            default => 500,
        };

        return $responder->respond($responseFactory->createResponse(), [
            'error' => [
                'code' => errorCode($status),
                'message' => $status === 500 ? 'Unexpected server error.' : $exception->getMessage(),
            ],
        ], $status);
    };
}

function errorCode(int $status): string
{
    return match ($status) {
        400 => 'bad_request',
        404 => 'not_found',
        409 => 'conflict',
        422 => 'business_rule_violation',
        default => 'internal_server_error',
    };
}
