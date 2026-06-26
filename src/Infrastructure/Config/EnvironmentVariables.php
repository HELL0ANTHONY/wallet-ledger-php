<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

final class EnvironmentVariables
{
    public const string APP_DEBUG = 'APP_DEBUG';
    public const string APP_ENV = 'APP_ENV';
    public const string DATABASE_DSN = 'DATABASE_DSN';
    public const string DATABASE_PATH = 'DATABASE_PATH';

    /**
     * @return list<string>
     */
    public static function required(): array
    {
        return [
            self::APP_ENV,
            self::APP_DEBUG,
            self::DATABASE_DSN,
            self::DATABASE_PATH,
        ];
    }
}
