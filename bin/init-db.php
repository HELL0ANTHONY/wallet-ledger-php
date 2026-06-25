<?php

declare(strict_types=1);

use WalletLedger\Infrastructure\Config\SettingsFactory;
use WalletLedger\Infrastructure\Database\PdoConnectionFactory;
use WalletLedger\Infrastructure\Database\SchemaInitializer;

require_once __DIR__ . '/../vendor/autoload.php';

$settings = (new SettingsFactory())->fromGlobals();
$databaseDirectory = dirname(__DIR__ . '/' . $settings->database->path);

if (!is_dir($databaseDirectory)) {
    mkdir($databaseDirectory, 0775, true);
}

$pdo = (new PdoConnectionFactory($settings->database))->create();
(new SchemaInitializer($pdo, __DIR__ . '/../database/schema.sql'))->initialize();

echo sprintf("Database initialized: %s\n", $settings->database->dsn);
