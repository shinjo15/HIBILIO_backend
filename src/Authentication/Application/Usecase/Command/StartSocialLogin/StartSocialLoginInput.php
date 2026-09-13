<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\StartSocialLogin;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

final readonly class StartSocialLoginInput implements StartSocialLoginInputPort
{
    public function __construct(
        private SocialLoginProvider $provider,
        private ?string $accountName,
        private string $browserSessionIdentifier,
    ) {}

    public function provider(): SocialLoginProvider
    {
        return $this->provider;
    }

    public function accountName(): ?string
    {
        return $this->accountName;
    }

    public function browserSessionIdentifier(): string
    {
        return $this->browserSessionIdentifier;
    }
}
