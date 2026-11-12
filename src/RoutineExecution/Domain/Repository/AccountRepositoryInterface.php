<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\Account;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AccountRepositoryInterface
{
    /** Must be called within the transaction that saves experience. */
    public function findForUpdate(AccountIdentifier $accountIdentifier): ?Account;

    /** Persist experience from an account locked in the same transaction. */
    public function save(Account $account): void;
}
