<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Factory;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;

final class RoutineExecutionActionFactory implements RoutineExecutionActionFactoryInterface
{
    public function create(
        RoutineExecutionIdentifier $routineExecutionIdentifier,
        RoutineActionIdentifier $routineActionIdentifier,
    ): RoutineExecutionAction {
        return new RoutineExecutionAction(
            $routineExecutionIdentifier,
            $routineActionIdentifier,
        );
    }
}
