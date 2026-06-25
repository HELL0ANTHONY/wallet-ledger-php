<?php

declare(strict_types=1);

use WalletLedger\Infrastructure\Config\SettingsFactory;

require_once __DIR__ . '/../vendor/autoload.php';

$settings = (new SettingsFactory())->fromGlobals();

header('Content-Type: application/json');

echo json_encode([
    'environment' => $settings->app->environment->value,
    'service' => 'wallet-ledger-php',
    'status' => 'ok',
], JSON_THROW_ON_ERROR);
