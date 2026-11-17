<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LoginPasscodeChallengeTest extends TestCase
{
    public function test_accepts_a_six_digit_login_passcode(): void
    {
        self::assertSame('000123', (new LoginPasscode('000123'))->value());
    }

    public function test_rejects_a_non_six_digit_login_passcode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ログインパスコードは6桁の数字である必要があります。');

        new LoginPasscode('12345a');
    }

    public function test_challenge_keeps_only_temporary_authentication_state(): void
    {
        $challenge = new LoginPasscodeChallenge(
            new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new EmailAddress('user@example.com'),
            new LoginPasscodeHash('$2y$12$opaque-hash'),
        );

        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $challenge->identifier()->value());
        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $challenge->accountIdentifier()->value());
        self::assertSame('user@example.com', $challenge->emailAddress()->value());
        self::assertSame('$2y$12$opaque-hash', $challenge->passcodeHash()->value());
        self::assertSame(600, LoginPasscodeChallenge::EXPIRATION_SECONDS);
        self::assertSame(5, LoginPasscodeChallenge::MAX_FAILED_ATTEMPTS);
    }
}
