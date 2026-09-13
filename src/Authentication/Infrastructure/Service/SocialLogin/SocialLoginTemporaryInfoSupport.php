<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin;

use InvalidArgumentException;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

final readonly class SocialLoginTemporaryInfoSupport
{
    public function __construct(
        private string $state,
        private SocialLoginProvider $provider,
        private string $browserSessionHash,
        private string $codeVerifier,
        private string $nonce,
        private ?string $accountName,
    ) {
        foreach ([$state, $browserSessionHash, $codeVerifier, $nonce] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('ソーシャルログイン一時情報は空にできません。');
            }
        }
        if ($accountName !== null && trim($accountName) === '') {
            throw new InvalidArgumentException('アカウント名は空にできません。');
        }
    }

    public function state(): string
    {
        return $this->state;
    }

    public function provider(): SocialLoginProvider
    {
        return $this->provider;
    }

    public function browserSessionHash(): string
    {
        return $this->browserSessionHash;
    }

    public function codeVerifier(): string
    {
        return $this->codeVerifier;
    }

    public function nonce(): string
    {
        return $this->nonce;
    }

    public function accountName(): ?string
    {
        return $this->accountName;
    }
}
