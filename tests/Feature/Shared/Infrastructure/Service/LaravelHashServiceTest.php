<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Infrastructure\Service;

use Src\Shared\Application\Service\HashServiceInterface;
use Tests\TestCase;

final class LaravelHashServiceTest extends TestCase
{
    public function test_hashes_and_matches_a_value(): void
    {
        $service = $this->app->make(HashServiceInterface::class);
        $hash = $service->hash('raw-validator');

        self::assertNotSame('raw-validator', $hash);
        self::assertTrue($service->matches('raw-validator', $hash));
        self::assertFalse($service->matches('different-validator', $hash));
    }
}
