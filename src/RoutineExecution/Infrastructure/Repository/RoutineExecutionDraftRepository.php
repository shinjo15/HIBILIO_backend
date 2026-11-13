<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\RoutineExecutionDraftModel;
use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final class RoutineExecutionDraftRepository implements RoutineExecutionDraftRepositoryInterface
{
    public function save(RoutineExecution $routineExecution): void
    {
        RoutineExecutionDraftModel::query()->create([
            'routine_execution_identifier' => $routineExecution->routineExecutionIdentifier()->value(),
            'executor_account_identifier' => $routineExecution->executorAccountIdentifier()->value(),
            'routine_identifier' => $routineExecution->routineIdentifier()->value(),
            'executed_at' => $routineExecution->executedAt()->value(),
            'routine_execution_memo' => $routineExecution->routineExecutionMemo()?->value(),
        ]);
    }
}
