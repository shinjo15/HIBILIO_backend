<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Factory;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;

interface RoutineExecutionActionFactoryInterface
{
    public function create(
        RoutineExecutionIdentifier $routineExecutionIdentifier,
        RoutineActionIdentifier $routineActionIdentifier,
    ): RoutineExecutionAction;
}
