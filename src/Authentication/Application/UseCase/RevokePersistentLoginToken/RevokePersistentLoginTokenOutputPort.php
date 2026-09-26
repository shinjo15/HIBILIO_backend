<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\RevokePersistentLoginToken;

interface RevokePersistentLoginTokenOutputPort
{
    public function revoked(): bool;
}
