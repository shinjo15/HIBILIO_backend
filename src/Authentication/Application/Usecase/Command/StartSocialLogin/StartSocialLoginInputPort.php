<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\StartSocialLogin;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

interface StartSocialLoginInputPort
{
    public function provider(): SocialLoginProvider;

    public function accountName(): ?string;

    public function browserSessionIdentifier(): string;
}
