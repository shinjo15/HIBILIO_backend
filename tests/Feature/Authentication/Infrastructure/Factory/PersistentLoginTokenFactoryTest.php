<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Src\Authentication\Infrastructure\Factory\PersistentLoginTokenFactory;
use Src\Shared\Application\Service\HashServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class PersistentLoginTokenFactoryTest extends TestCase
{
    public function test_generates_unique_hex_selectors(): void
    {
        $factory = new PersistentLoginTokenFactory($this->app->make(HashServiceInterface::class));
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $issuedAt = new DateTimeImmutable('2099-01-01 00:00:00');

        $firstGeneratedToken = $factory->create($accountIdentifier, $issuedAt);
        $secondGeneratedToken = $factory->create($accountIdentifier, $issuedAt);

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/D', $firstGeneratedToken->persistentLoginToken()->selector()->value());
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/D', $secondGeneratedToken->persistentLoginToken()->selector()->value());
        self::assertNotSame(
            $firstGeneratedToken->persistentLoginToken()->selector()->value(),
            $secondGeneratedToken->persistentLoginToken()->selector()->value(),
        );
    }

    public function test_hashes_the_raw_validator_and_sets_expiry_from_issuance(): void
    {
        $generatedToken = (new PersistentLoginTokenFactory($this->app->make(HashServiceInterface::class)))->create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new DateTimeImmutable('2099-01-01 00:00:00'),
        );

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $generatedToken->rawValidator());
        self::assertNotSame($generatedToken->rawValidator(), $generatedToken->persistentLoginToken()->validatorHash()->value());
        self::assertTrue(Hash::check($generatedToken->rawValidator(), $generatedToken->persistentLoginToken()->validatorHash()->value()));
        self::assertSame('2099-01-31 00:00:00', $generatedToken->persistentLoginToken()->expiresAt()->value()->format('Y-m-d H:i:s'));
    }
}
