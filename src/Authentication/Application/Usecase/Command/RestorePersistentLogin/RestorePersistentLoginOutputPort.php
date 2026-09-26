<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\RestorePersistentLogin;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface RestorePersistentLoginOutputPort
{
    public function accountIdentifier(): ?AccountIdentifier;
}
