<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\IssuePersistentLogin;

interface IssuePersistentLoginInterface
{
    public function execute(IssuePersistentLoginInputPort $input): IssuePersistentLoginOutputPort;
}
