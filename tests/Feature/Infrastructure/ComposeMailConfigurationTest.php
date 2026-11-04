<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class ComposeMailConfigurationTest extends TestCase
{
    public function test_app_service_uses_mailpit_smtp_configuration_without_a_local_env_file(): void
    {
        $compose = file_get_contents(base_path('compose.override.yml'));

        self::assertIsString($compose);
        self::assertStringContainsString('MAIL_MAILER=smtp', $compose);
        self::assertStringContainsString('MAIL_HOST=mailpit', $compose);
        self::assertStringContainsString('MAIL_PORT=1025', $compose);
        self::assertStringContainsString('MAIL_FROM_ADDRESS=no-reply@hibilio.local', $compose);
        self::assertStringContainsString('MAIL_FROM_NAME=${APP_NAME}', $compose);
        $envExample = file_get_contents(base_path('.env.example'));

        self::assertIsString($envExample);
        self::assertStringContainsString('APP_NAME=HIBILIO', $envExample);
        self::assertStringContainsString('${MAILPIT_PORT:-8026}:8025', $compose);
    }
}
