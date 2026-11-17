<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Infrastructure;

use Illuminate\Support\Facades\Redis;
use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Authentication\Infrastructure\Service\RedisLoginPasscodeStateService;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class RedisLoginPasscodeStateServiceTest extends TestCase
{
    public function test_persists_only_temporary_challenge_state_with_ttl_and_atomic_failures(): void
    {
        $service = new RedisLoginPasscodeStateService;
        $challenge = new LoginPasscodeChallenge(
            new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new EmailAddress('user@example.com'),
            new LoginPasscodeHash('opaque-hash'),
        );
        $key = 'login-passcode:challenge:'.$challenge->identifier()->value();
        Redis::del($key);

        $service->register($challenge);

        self::assertGreaterThan(0, Redis::ttl($key));
        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $service->find($challenge->identifier())?->accountIdentifier()->value());
        for ($attempt = 1; $attempt <= LoginPasscodeChallenge::MAX_FAILED_ATTEMPTS; $attempt++) {
            self::assertSame($attempt, $service->recordFailedAttempt($challenge->identifier()));
        }
        self::assertNull($service->find($challenge->identifier()));
    }
}
