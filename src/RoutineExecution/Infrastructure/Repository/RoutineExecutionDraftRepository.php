<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraftRepository implements RoutineExecutionDraftRepositoryInterface
{
    public function exists(AccountIdentifier $accountIdentifier, RoutineIdentifier $routineIdentifier): bool
    {
        return DB::table('routine_execution_drafts')->where('executor_account_identifier', $accountIdentifier->value())->where('routine_identifier', $routineIdentifier->value())->exists();
    }

    public function save(RoutineExecution $routineExecution): void
    {
        DB::table('routine_execution_drafts')->insert([
            'routine_execution_identifier' => $routineExecution->routineExecutionIdentifier()->value(),
            'executor_account_identifier' => $routineExecution->executorAccountIdentifier()->value(),
            'routine_identifier' => $routineExecution->routineIdentifier()->value(),
            'executed_at' => $routineExecution->executedAt()->value(),
            'routine_execution_memo' => $routineExecution->routineExecutionMemo()?->value(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
