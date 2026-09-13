<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use InvalidArgumentException;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;

final readonly class SocialLoginTemporaryInfoService
{
    public function generate(
        SocialLoginProvider $provider,
        ?string $accountName,
        string $browserSessionIdentifier,
    ): SocialLoginTemporaryInfoSupport {
        if (trim($browserSessionIdentifier) === '') {
            throw new InvalidArgumentException('ブラウザーセッション識別子は空にできません。');
        }

        return new SocialLoginTemporaryInfoSupport(
            state: $this->randomToken(32),
            provider: $provider,
            browserSessionHash: hash('sha256', $browserSessionIdentifier),
            codeVerifier: $this->randomToken(64),
            nonce: $this->randomToken(32),
            accountName: $accountName,
        );
    }

    private function randomToken(int $bytes): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}
