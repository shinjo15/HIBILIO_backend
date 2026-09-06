<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;

interface RoutineExecutionActionRepositoryInterface
{
    public function save(RoutineExecutionAction $routineExecutionAction): void;
}
