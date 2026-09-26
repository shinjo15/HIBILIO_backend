<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GeneratePersistentLoginToken;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface GeneratePersistentLoginTokenInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
