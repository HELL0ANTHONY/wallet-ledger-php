<?php

declare(strict_types=1);

use WalletLedger\Infrastructure\Config\SettingsFactory;
use WalletLedger\Infrastructure\Database\PdoConnectionFactory;
use WalletLedger\Infrastructure\Database\SchemaInitializer;

require_once __DIR__ . '/../vendor/autoload.php';

$settings = (new SettingsFactory())->fromGlobals();
$database = $settings->database->withProjectRoot(dirname(__DIR__));
$databaseDirectory = dirname($database->path);

if (!is_dir($databaseDirectory)) {
    mkdir($databaseDirectory, 0o775, true);
}

$pdo = (new PdoConnectionFactory($database))->create();
(new SchemaInitializer($pdo, __DIR__ . '/../database/schema.sql'))->initialize();

echo sprintf("Database initialized: %s\n", $database->dsn);
