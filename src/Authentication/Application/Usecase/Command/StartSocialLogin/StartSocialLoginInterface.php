<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\StartSocialLogin;

interface StartSocialLoginInterface
{
    public function execute(StartSocialLoginInputPort $input): StartSocialLoginOutputPort;
}
