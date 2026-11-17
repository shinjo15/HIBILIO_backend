<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Authentication\Infrastructure\Factory\LoginPasscodeChallengeFactory;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LoginPasscodeChallengeFactoryTest extends TestCase
{
    public function test_creates_a_challenge_with_the_uuid_service_identifier(): void
    {
        $challenge = (new LoginPasscodeChallengeFactory(new FixedUuidService))->create(
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new EmailAddress('user@example.com'),
            new LoginPasscodeHash('opaque-hash'),
        );

        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $challenge->identifier()->value());
    }
}

final class FixedUuidService implements UuidServiceInterface
{
    public function generate(): string
    {
        return '3b5581e9-16df-4879-b7d2-5d88dca6ab87';
    }
}
