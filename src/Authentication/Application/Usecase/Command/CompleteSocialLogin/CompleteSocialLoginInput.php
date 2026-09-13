<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

final readonly class CompleteSocialLoginInput implements CompleteSocialLoginInputPort
{
    public function __construct(
        private SocialLoginProvider $provider,
        private string $authorizationCode,
        private string $state,
        private string $browserSessionIdentifier,
    ) {}

    public function provider(): SocialLoginProvider
    {
        return $this->provider;
    }

    public function authorizationCode(): string
    {
        return $this->authorizationCode;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function browserSessionIdentifier(): string
    {
        return $this->browserSessionIdentifier;
    }
}
