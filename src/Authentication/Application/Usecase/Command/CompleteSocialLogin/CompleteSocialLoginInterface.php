<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

interface CompleteSocialLoginInterface
{
    public function execute(CompleteSocialLoginInputPort $input): CompleteSocialLoginOutputPort;
}
