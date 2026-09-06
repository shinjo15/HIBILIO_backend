<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Entity;

use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;

final readonly class RoutineExecutionAction
{
    public function __construct(
        private RoutineExecutionIdentifier $routineExecutionIdentifier,
        private RoutineActionIdentifier $routineActionIdentifier,
    ) {}

    public function routineExecutionIdentifier(): RoutineExecutionIdentifier
    {
        return $this->routineExecutionIdentifier;
    }

    public function routineActionIdentifier(): RoutineActionIdentifier
    {
        return $this->routineActionIdentifier;
    }
}
