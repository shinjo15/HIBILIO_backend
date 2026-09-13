<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\StartSocialLogin;

final readonly class StartSocialLoginOutput implements StartSocialLoginOutputPort
{
    public function __construct(private string $authorizationUrl) {}

    public function authorizationUrl(): string
    {
        return $this->authorizationUrl;
    }
}
