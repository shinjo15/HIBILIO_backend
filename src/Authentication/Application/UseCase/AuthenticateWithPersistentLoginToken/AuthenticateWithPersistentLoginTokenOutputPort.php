<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken;

use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AuthenticateWithPersistentLoginTokenOutputPort
{
    public function accountIdentifier(): ?AccountIdentifier;

    public function persistentLoginToken(): ?GeneratePersistentLoginTokenOutputPort;
}
