<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Factory;

use Src\RoutineExecution\Domain\Entity\Account;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AccountFactoryInterface
{
    public function restore(AccountIdentifier $accountIdentifier, int $experience): Account;
}
