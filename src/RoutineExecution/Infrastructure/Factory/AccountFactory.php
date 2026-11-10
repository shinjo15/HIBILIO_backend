<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Factory;

use Src\RoutineExecution\Domain\Entity\Account;
use Src\RoutineExecution\Domain\Factory\AccountFactoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AccountFactory implements AccountFactoryInterface
{
    public function restore(AccountIdentifier $accountIdentifier, int $experience): Account
    {
        return new Account($accountIdentifier, $experience);
    }
}
