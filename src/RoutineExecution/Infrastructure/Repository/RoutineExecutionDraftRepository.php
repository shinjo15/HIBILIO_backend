<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\RoutineExecutionDraftModel;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final class RoutineExecutionDraftRepository implements RoutineExecutionDraftRepositoryInterface
{
    public function save(RoutineExecutionDraft $routineExecutionDraft): void
    {
        RoutineExecutionDraftModel::query()->create([
            'routine_execution_identifier' => $routineExecutionDraft->routineExecutionIdentifier()->value(),
            'executor_account_identifier' => $routineExecutionDraft->executorAccountIdentifier()->value(),
            'routine_identifier' => $routineExecutionDraft->routineIdentifier()->value(),
            'executed_at' => $routineExecutionDraft->executedAt()->value(),
            'routine_execution_memo' => $routineExecutionDraft->routineExecutionMemo()?->value(),
        ]);
    }
}
