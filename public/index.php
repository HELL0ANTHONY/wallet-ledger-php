<?php

declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode([
    'service' => 'wallet-ledger-php',
    'status' => 'ok',
], JSON_THROW_ON_ERROR);
