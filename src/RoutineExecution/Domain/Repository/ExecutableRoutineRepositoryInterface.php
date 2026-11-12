<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\ValueObject\ExecutableRoutine;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface ExecutableRoutineRepositoryInterface
{
    public function find(RoutineIdentifier $routineIdentifier, AccountIdentifier $viewer): ?ExecutableRoutine;
}
