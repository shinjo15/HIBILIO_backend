<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken;

interface AuthenticateWithPersistentLoginTokenInterface
{
    public function execute(AuthenticateWithPersistentLoginTokenInputPort $input): AuthenticateWithPersistentLoginTokenOutputPort;
}
