<?php

declare(strict_types=1);

use Slim\App;
use WalletLedger\Http\Controller\AccountController;
use WalletLedger\Http\Controller\LedgerController;

return static function (
    App $app,
    AccountController $accountController,
    LedgerController $ledgerController,
): void {
    $app->post('/accounts', $accountController->create(...));
    $app->get('/accounts/{id}', $accountController->get(...));
    $app->post('/accounts/{id}/deposits', $ledgerController->deposit(...));
    $app->post('/accounts/{id}/withdrawals', $ledgerController->withdraw(...));
    $app->post('/transfers', $ledgerController->transfer(...));
    $app->get('/accounts/{id}/transactions', $ledgerController->list(...));
};
