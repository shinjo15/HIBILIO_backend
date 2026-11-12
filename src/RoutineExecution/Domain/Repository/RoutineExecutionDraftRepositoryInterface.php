<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineExecutionDraftRepositoryInterface
{
    public function exists(AccountIdentifier $accountIdentifier, RoutineIdentifier $routineIdentifier): bool;

    public function save(RoutineExecution $routineExecution): void;
}
