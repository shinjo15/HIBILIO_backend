<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\RevokePersistentLoginToken;

interface RevokePersistentLoginTokenInterface
{
    public function execute(RevokePersistentLoginTokenInputPort $input): RevokePersistentLoginTokenOutputPort;
}
