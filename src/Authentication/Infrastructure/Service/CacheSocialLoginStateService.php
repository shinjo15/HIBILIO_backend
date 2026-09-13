<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Support\Facades\Cache;
use JsonException;
use RuntimeException;
use Src\Authentication\Application\Service\SocialLoginStateServiceInterface;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;

final class CacheSocialLoginStateService implements SocialLoginStateServiceInterface
{
    public const EXPIRATION_SECONDS = 600;

    private const KEY_PREFIX = 'social-login:state:';

    /** @throws JsonException */
    public function store(SocialLoginTemporaryInfoSupport $temporaryInfo): void
    {
        $stored = Cache::put(
            self::KEY_PREFIX.$temporaryInfo->state(),
            json_encode([
                'provider' => $temporaryInfo->provider()->value,
                'browser_session_hash' => $temporaryInfo->browserSessionHash(),
                'code_verifier' => $temporaryInfo->codeVerifier(),
                'nonce' => $temporaryInfo->nonce(),
                'account_name' => $temporaryInfo->accountName(),
            ], JSON_THROW_ON_ERROR),
            now()->addSeconds(self::EXPIRATION_SECONDS),
        );

        if ($stored === false) {
            throw new RuntimeException('ソーシャルログイン一時情報を保存できませんでした。');
        }
    }

    /** @throws JsonException */
    public function consume(
        string $state,
        SocialLoginProvider $provider,
        string $browserSessionIdentifier,
    ): ?SocialLoginTemporaryInfoSupport {
        if (trim($state) === '' || trim($browserSessionIdentifier) === '') {
            return null;
        }

        $payload = Cache::lock(self::KEY_PREFIX.'consume:'.$state, 10)->block(
            5,
            fn (): mixed => Cache::pull(self::KEY_PREFIX.$state),
        );
        if (! is_string($payload)) {
            return null;
        }

        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! $this->isValidPayload($data)) {
            return null;
        }

        if (
            ! hash_equals($data['provider'], $provider->value)
            || ! hash_equals($data['browser_session_hash'], hash('sha256', $browserSessionIdentifier))
        ) {
            return null;
        }

        return new SocialLoginTemporaryInfoSupport(
            $state,
            SocialLoginProvider::from($data['provider']),
            $data['browser_session_hash'],
            $data['code_verifier'],
            $data['nonce'],
            $data['account_name'],
        );
    }

    /** @param array<mixed> $data */
    private function isValidPayload(array $data): bool
    {
        return isset($data['provider'], $data['browser_session_hash'], $data['code_verifier'], $data['nonce'])
            && is_string($data['provider'])
            && is_string($data['browser_session_hash'])
            && is_string($data['code_verifier'])
            && is_string($data['nonce'])
            && ($data['account_name'] === null || is_string($data['account_name']));
    }
}
