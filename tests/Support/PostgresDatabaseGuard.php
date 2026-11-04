<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Application;
use PDO;
use RuntimeException;

final class PostgresDatabaseGuard
{
    public static function prepareEnvironment(): void
    {
        if (getenv('POSTGRES_TEST_ALLOW_RESET') !== 'hibilio_postgres_test') {
            throw new RuntimeException('Run PostgreSQL tests only through compose.postgres-test.yml.');
        }

        $environment = [
            'APP_ENV' => 'testing',
            'APP_BASE_PATH' => dirname(__DIR__, 2),
            'APP_CONFIG_CACHE' => '/tmp/hibilio-postgres-test-no-config-cache.php',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => 'postgres',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'hibilio_postgres_test',
            'DB_USERNAME' => 'hibilio_fixture',
            'DB_PASSWORD' => 'fixture-only-not-a-production-secret',
            'DB_URL' => '',
            'DB_SSLMODE' => 'disable',
            'DB_SSLROOTCERT' => '',
        ];

        foreach ($environment as $key => $expected) {
            foreach ([$_SERVER[$key] ?? null, $_ENV[$key] ?? null, getenv($key)] as $actual) {
                if ($actual !== null && $actual !== false && $actual !== $expected) {
                    throw new RuntimeException('Unsafe PostgreSQL test environment: '.$key);
                }
            }

            $_SERVER[$key] = $_ENV[$key] = $expected;
            putenv($key.'='.$expected);
        }

        if (file_exists($environment['APP_CONFIG_CACHE'])) {
            throw new RuntimeException('Unsafe PostgreSQL test environment: cached configuration exists.');
        }
    }

    public static function assertConnection(Application $application): void
    {
        if ($application->getCachedConfigPath() !== '/tmp/hibilio-postgres-test-no-config-cache.php'
            || $application->configurationIsCached()
            || $application['config']->get('database.default') !== 'pgsql') {
            throw new RuntimeException('Unsafe PostgreSQL test configuration or cache path.');
        }

        $declared = $application['config']->get('database.connections.pgsql');

        if (! empty($declared['url']) || isset($declared['read']) || isset($declared['write'])) {
            throw new RuntimeException('Unsafe PostgreSQL test connection: URL or read/write routing.');
        }

        $connection = $application['db']->connection();
        $configuration = $connection->getConfig();
        $expected = [
            'driver' => 'pgsql',
            'host' => 'postgres',
            'port' => '5432',
            'database' => 'hibilio_postgres_test',
            'username' => 'hibilio_fixture',
            'search_path' => 'public',
        ];

        foreach ($expected as $key => $value) {
            if ((string) ($configuration[$key] ?? '') !== $value) {
                throw new RuntimeException('Unsafe PostgreSQL test connection: '.$key);
            }
        }

        if (! empty($configuration['url']) || isset($configuration['read']) || isset($configuration['write'])) {
            throw new RuntimeException('Unsafe PostgreSQL test connection: URL or read/write routing.');
        }

        $identity = $connection->getPdo()->query("select current_database() as name, current_user as username, current_schema() as schema, inet_server_port() as port, shobj_description(oid, 'pg_database') as marker from pg_database where datname = current_database()")->fetch(PDO::FETCH_ASSOC);

        if ($identity !== [
            'name' => 'hibilio_postgres_test',
            'username' => 'hibilio_fixture',
            'schema' => 'public',
            'port' => 5432,
            'marker' => 'hibilio disposable postgres regression fixture',
        ]) {
            throw new RuntimeException('Unsafe PostgreSQL write database: disposable fixture identity mismatch.');
        }
    }
}
