<?php

declare(strict_types=1);

namespace WalletLedger\Tests\Unit\Infrastructure\Config;

use PHPUnit\Framework\TestCase;
use WalletLedger\Infrastructure\Config\AppEnvironment;
use WalletLedger\Infrastructure\Config\EnvironmentVariables;
use WalletLedger\Infrastructure\Config\InvalidConfigurationException;
use WalletLedger\Infrastructure\Config\SettingsFactory;

final class SettingsFactoryTest extends TestCase
{
    public function test_it_builds_typed_settings_from_array(): void
    {
        $settings = (new SettingsFactory())->fromArray($this->validValues());

        self::assertSame(AppEnvironment::Local, $settings->app->environment);
        self::assertTrue($settings->app->debug);
        self::assertSame('sqlite:var/wallet-ledger.sqlite', $settings->database->dsn);
        self::assertSame('var/wallet-ledger.sqlite', $settings->database->path);
    }

    public function test_it_rejects_missing_required_values(): void
    {
        $values = $this->validValues();
        unset($values[EnvironmentVariables::APP_ENV]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Missing required environment variable: APP_ENV');

        (new SettingsFactory())->fromArray($values);
    }

    public function test_it_rejects_invalid_environment(): void
    {
        $values = $this->validValues();
        $values[EnvironmentVariables::APP_ENV] = 'staging';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid environment variable APP_ENV');

        (new SettingsFactory())->fromArray($values);
    }

    public function test_it_rejects_invalid_boolean_debug_value(): void
    {
        $values = $this->validValues();
        $values[EnvironmentVariables::APP_DEBUG] = 'maybe';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid environment variable APP_DEBUG');

        (new SettingsFactory())->fromArray($values);
    }

    /**
     * @return array<string, string>
     */
    private function validValues(): array
    {
        return [
            EnvironmentVariables::APP_ENV => 'local',
            EnvironmentVariables::APP_DEBUG => 'true',
            EnvironmentVariables::DATABASE_DSN => 'sqlite:var/wallet-ledger.sqlite',
            EnvironmentVariables::DATABASE_PATH => 'var/wallet-ledger.sqlite',
        ];
    }
}
