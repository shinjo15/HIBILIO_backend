<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Entity;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class SocialLoginConnection
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private SocialLoginProvider $provider,
        private string $providerUserIdentifier,
    ) {
        if (trim($providerUserIdentifier) === '' || strlen($providerUserIdentifier) > 255) {
            throw new \InvalidArgumentException('ソーシャルログインのユーザー識別子が不正です。');
        }
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function provider(): SocialLoginProvider
    {
        return $this->provider;
    }

    public function providerUserIdentifier(): string
    {
        return $this->providerUserIdentifier;
    }
}
