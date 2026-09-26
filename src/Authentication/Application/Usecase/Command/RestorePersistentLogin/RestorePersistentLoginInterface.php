<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\RestorePersistentLogin;

interface RestorePersistentLoginInterface
{
    public function execute(RestorePersistentLoginInputPort $input): RestorePersistentLoginOutputPort;
}
