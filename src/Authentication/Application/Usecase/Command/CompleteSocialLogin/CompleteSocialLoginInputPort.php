<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

interface CompleteSocialLoginInputPort
{
    public function provider(): SocialLoginProvider;

    public function authorizationCode(): string;

    public function state(): string;

    public function browserSessionIdentifier(): string;
}
