<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken;

use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

interface AuthenticateWithPersistentLoginTokenInputPort
{
    public function selector(): PersistentLoginSelector;

    public function rawValidator(): string;
}
