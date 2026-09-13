<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\StartSocialLogin;

interface StartSocialLoginOutputPort
{
    public function authorizationUrl(): string;
}
