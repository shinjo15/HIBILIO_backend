<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Support\Facades\Redis;
use JsonException;
use RuntimeException;
use Src\Authentication\Application\Service\LoginPasscodeStateServiceInterface;
use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class RedisLoginPasscodeStateService implements LoginPasscodeStateServiceInterface
{
    private const KEY_PREFIX = 'login-passcode:challenge:';

    private const RECORD_FAILED_ATTEMPT_SCRIPT = <<<'LUA'
        local payload = redis.call('GET', KEYS[1])
        if not payload then
            return -1
        end

        local state = cjson.decode(payload)
        local failed_attempts = (tonumber(state['failed_attempts']) or 0) + 1
        if failed_attempts >= tonumber(ARGV[1]) then
            redis.call('DEL', KEYS[1])
            return failed_attempts
        end

        state['failed_attempts'] = failed_attempts
        redis.call('SET', KEYS[1], cjson.encode(state), 'KEEPTTL')
        return failed_attempts
        LUA;

    public function register(LoginPasscodeChallenge $challenge): void
    {
        Redis::setex(
            $this->key($challenge->identifier()),
            LoginPasscodeChallenge::EXPIRATION_SECONDS,
            json_encode([
                'account_identifier' => $challenge->accountIdentifier()->value(),
                'email_address' => $challenge->emailAddress()->value(),
                'passcode_hash' => $challenge->passcodeHash()->value(),
                'failed_attempts' => 0,
            ], JSON_THROW_ON_ERROR),
        );
    }

    public function find(LoginPasscodeChallengeIdentifier $challengeIdentifier): ?LoginPasscodeChallenge
    {
        $payload = Redis::get($this->key($challengeIdentifier));
        if (! is_string($payload)) {
            return null;
        }

        $state = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        if (
            ! is_array($state)
            || ! isset($state['account_identifier'], $state['email_address'], $state['passcode_hash'], $state['failed_attempts'])
            || ! is_string($state['account_identifier'])
            || ! is_string($state['email_address'])
            || ! is_string($state['passcode_hash'])
            || ! is_int($state['failed_attempts'])
        ) {
            throw new JsonException('ログインパスコードの状態が不正です。');
        }

        return new LoginPasscodeChallenge(
            $challengeIdentifier,
            new AccountIdentifier($state['account_identifier']),
            new EmailAddress($state['email_address']),
            new LoginPasscodeHash($state['passcode_hash']),
        );
    }

    public function recordFailedAttempt(LoginPasscodeChallengeIdentifier $challengeIdentifier): ?int
    {
        $failedAttempts = Redis::command('eval', [
            self::RECORD_FAILED_ATTEMPT_SCRIPT,
            [$this->key($challengeIdentifier), (string) LoginPasscodeChallenge::MAX_FAILED_ATTEMPTS],
            1,
        ]);

        if (! is_int($failedAttempts)) {
            throw new RuntimeException('Redisから不正な認証失敗回数が返されました。');
        }

        return $failedAttempts < 0 ? null : $failedAttempts;
    }

    public function delete(LoginPasscodeChallengeIdentifier $challengeIdentifier): bool
    {
        return Redis::del($this->key($challengeIdentifier)) === 1;
    }

    private function key(LoginPasscodeChallengeIdentifier $challengeIdentifier): string
    {
        return self::KEY_PREFIX.$challengeIdentifier->value();
    }
}
