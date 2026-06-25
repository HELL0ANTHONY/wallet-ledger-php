<?php

declare(strict_types=1);

namespace WalletLedger\Infrastructure\Config;

use function array_key_exists;
use function is_bool;
use function is_int;

final readonly class SettingsFactory
{
    public function __construct(
        private EnvironmentReader $environmentReader = new EnvironmentReader(),
    ) {}

    public function fromGlobals(): Settings
    {
        return $this->fromArray($this->environmentReader->readGlobals());
    }

    /**
     * @param array<string, string> $values
     */
    public function fromArray(array $values): Settings
    {
        foreach (EnvironmentVariables::required() as $name) {
            if (!array_key_exists($name, $values) || $values[$name] === '') {
                throw InvalidConfigurationException::missing($name);
            }
        }

        $environment = AppEnvironment::tryFrom($values[EnvironmentVariables::APP_ENV]);
        if (!$environment instanceof AppEnvironment) {
            throw InvalidConfigurationException::invalid(
                EnvironmentVariables::APP_ENV,
                'one of: local, test, production',
            );
        }

        $debug = filter_var($values[EnvironmentVariables::APP_DEBUG], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if (!is_bool($debug)) {
            throw InvalidConfigurationException::invalid(EnvironmentVariables::APP_DEBUG, 'a boolean value');
        }

        $port = filter_var($values[EnvironmentVariables::APP_PORT], FILTER_VALIDATE_INT);
        if (!is_int($port)) {
            throw InvalidConfigurationException::invalid(EnvironmentVariables::APP_PORT, 'an integer TCP port');
        }

        return new Settings(
            app: new AppConfig(
                environment: $environment,
                debug: $debug,
                port: $port,
            ),
            database: new DatabaseConfig(
                dsn: $values[EnvironmentVariables::DATABASE_DSN],
                path: $values[EnvironmentVariables::DATABASE_PATH],
            ),
        );
    }
}
