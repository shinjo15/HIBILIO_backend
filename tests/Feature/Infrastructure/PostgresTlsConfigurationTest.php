<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Env;
use Tests\TestCase;

final class PostgresTlsConfigurationTest extends TestCase
{
    public function test_postgres_connection_passes_the_configured_ca_and_verification_mode_to_pdo(): void
    {
        Env::getRepository()->set('DB_SSLMODE', 'verify-full');
        Env::getRepository()->set('DB_SSLROOTCERT', '/run/secrets/postgres-ca.crt');

        try {
            $configuration = require base_path('config/database.php');
            $connector = new class extends PostgresConnector
            {
                public function dsn(array $configuration): string
                {
                    return $this->getDsn($configuration);
                }
            };

            $dsn = $connector->dsn($configuration['connections']['pgsql']);

            self::assertStringContainsString(';sslmode=verify-full', $dsn);
            self::assertStringContainsString(';sslrootcert=/run/secrets/postgres-ca.crt', $dsn);
        } finally {
            Env::getRepository()->clear('DB_SSLMODE');
            Env::getRepository()->clear('DB_SSLROOTCERT');
        }
    }
}
