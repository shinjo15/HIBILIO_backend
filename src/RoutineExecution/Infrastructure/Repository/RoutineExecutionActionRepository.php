<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\RoutineExecutionActionModel;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionRepositoryInterface;

final class RoutineExecutionActionRepository implements RoutineExecutionActionRepositoryInterface
{
    public function save(RoutineExecutionAction $routineExecutionAction): void
    {
        RoutineExecutionActionModel::query()->create([
            'routine_execution_identifier' => $routineExecutionAction->routineExecutionIdentifier()->value(),
            'routine_action_identifier' => $routineExecutionAction->routineActionIdentifier()->value(),
        ]);
    }
}
