<?php

declare(strict_types=1);

namespace Tests\Postgres;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\Support\PostgresDatabaseGuard;
use Tests\TestCase;

final class PostgresDatabaseGuardTest extends TestCase
{
    #[DataProvider('unsafeServerEnvironment')]
    public function test_rejects_server_environment_overrides_before_booting_or_resetting_the_database(string $key, string $value): void
    {
        $code = '$_SERVER['.var_export($key, true).'] = '.var_export($value, true).'; require "tests/Support/postgres-bootstrap.php";';
        $process = new Process([PHP_BINARY, '-r', $code], base_path());

        $process->run();

        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString('Unsafe PostgreSQL test environment', $process->getOutput().$process->getErrorOutput());
    }

    /** @return array<string, array{string, string}> */
    public static function unsafeServerEnvironment(): array
    {
        return [
            'host' => ['DB_HOST', 'not-the-fixture.invalid'],
            'database' => ['DB_DATABASE', 'not_the_fixture'],
            'url' => ['DB_URL', 'pgsql://not-the-fixture.invalid/not_the_fixture'],
            'config cache' => ['APP_CONFIG_CACHE', '/tmp/not-the-fixture-config.php'],
        ];
    }

    public function test_rejects_read_write_routing_before_resolving_a_connection(): void
    {
        $this->app['config']->set('database.connections.pgsql.read', ['host' => 'not-the-fixture.invalid']);
        $this->app['db']->purge('pgsql');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('URL or read/write routing');

        PostgresDatabaseGuard::assertConnection($this->app);
    }

    public function test_checks_the_actual_write_pdo_not_a_safe_read_pdo(): void
    {
        $connection = $this->app['db']->connection();
        $fixturePdo = $connection->getPdo();
        $otherPdo = new PDO('pgsql:host=postgres;port=5432;dbname=postgres;sslmode=disable', 'hibilio_fixture', 'fixture-only-not-a-production-secret');
        $connection->setReadPdo($fixturePdo);
        $connection->setPdo($otherPdo);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('write database');

        try {
            PostgresDatabaseGuard::assertConnection($this->app);
        } finally {
            $connection->setPdo($fixturePdo);
            $connection->setReadPdo($fixturePdo);
        }
    }

    public function test_rejects_a_write_database_without_the_fixture_marker(): void
    {
        $connection = $this->app['db']->connection();
        $connection->beginTransaction();
        $connection->getPdo()->exec("COMMENT ON DATABASE hibilio_postgres_test IS 'not a disposable fixture'");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('write database');

        try {
            PostgresDatabaseGuard::assertConnection($this->app);
        } finally {
            $connection->rollBack();
        }
    }

    public function test_rejects_a_changed_application_cache_path_after_booting(): void
    {
        $original = $_SERVER['APP_CONFIG_CACHE'];
        $_SERVER['APP_CONFIG_CACHE'] = '/tmp/not-the-fixture-config.php';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration or cache path');

        try {
            PostgresDatabaseGuard::assertConnection($this->app);
        } finally {
            $_SERVER['APP_CONFIG_CACHE'] = $original;
        }
    }
}
