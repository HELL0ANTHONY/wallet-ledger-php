<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

enum AppEnvironment: string
{
    case Local = 'local';
    case Production = 'production';
    case Test = 'test';
}
