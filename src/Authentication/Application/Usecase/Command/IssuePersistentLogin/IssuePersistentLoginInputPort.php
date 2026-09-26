<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\IssuePersistentLogin;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface IssuePersistentLoginInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
