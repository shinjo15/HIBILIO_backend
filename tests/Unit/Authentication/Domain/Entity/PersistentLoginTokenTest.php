<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class PersistentLoginTokenTest extends TestCase
{
    public function test_retains_only_hashed_persistent_login_state(): void
    {
        $selector = new PersistentLoginSelector('selector');
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $validatorHash = new PersistentLoginValidatorHash('$2y$12$opaque-hash');
        $expiresAt = new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-31 00:00:00'));

        $token = new PersistentLoginToken($selector, $accountIdentifier, $validatorHash, $expiresAt);

        self::assertSame($selector, $token->selector());
        self::assertSame($accountIdentifier, $token->accountIdentifier());
        self::assertSame($validatorHash, $token->validatorHash());
        self::assertSame($expiresAt, $token->expiresAt());
        self::assertSame(
            ['selector', 'accountIdentifier', 'validatorHash', 'expiresAt'],
            array_map(
                static fn ($parameter): string => $parameter->getName(),
                (new ReflectionClass(PersistentLoginToken::class))->getConstructor()->getParameters(),
            ),
        );
    }

    public function test_is_expired_at_or_after_its_expiration_time(): void
    {
        $token = new PersistentLoginToken(
            new PersistentLoginSelector('selector'),
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new PersistentLoginValidatorHash('$2y$12$opaque-hash'),
            new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-31 00:00:00')),
        );

        self::assertFalse($token->isExpired(new DateTimeImmutable('2099-01-30 23:59:59')));
        self::assertTrue($token->isExpired(new DateTimeImmutable('2099-01-31 00:00:00')));
    }
}
