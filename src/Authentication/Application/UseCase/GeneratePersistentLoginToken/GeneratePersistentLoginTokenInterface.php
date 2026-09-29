<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GeneratePersistentLoginToken;

interface GeneratePersistentLoginTokenInterface
{
    public function execute(GeneratePersistentLoginTokenInputPort $input): GeneratePersistentLoginTokenOutputPort;
}
