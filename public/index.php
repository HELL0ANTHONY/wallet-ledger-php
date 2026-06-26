<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$createApp = require __DIR__ . '/../config/app.php';
if (!is_callable($createApp)) {
    throw new RuntimeException('App config must return a callable.');
}

$app = $createApp();
if (!$app instanceof Slim\App) {
    throw new RuntimeException('App config must return a Slim app.');
}

$app->run();
