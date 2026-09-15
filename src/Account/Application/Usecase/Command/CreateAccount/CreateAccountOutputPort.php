<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateAccount;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface CreateAccountOutputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
