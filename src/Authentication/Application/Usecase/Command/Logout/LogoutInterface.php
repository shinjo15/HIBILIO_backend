<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\Logout;

interface LogoutInterface
{
    public function execute(LogoutInputPort $input): LogoutOutputPort;
}
