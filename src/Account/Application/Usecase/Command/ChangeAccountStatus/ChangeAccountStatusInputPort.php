<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountStatus;

use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeAccountStatusInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function status(): AccountStatus;
}
