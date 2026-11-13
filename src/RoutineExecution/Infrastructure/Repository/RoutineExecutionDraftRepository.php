<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\RoutineExecutionDraftModel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftAlreadyExistsException;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraftRepository implements RoutineExecutionDraftRepositoryInterface
{
    public function find(AccountIdentifier $executorAccountIdentifier, RoutineIdentifier $routineIdentifier): ?RoutineExecutionDraft
    {
        $model = RoutineExecutionDraftModel::query()->where('executor_account_identifier', $executorAccountIdentifier->value())
            ->where('routine_identifier', $routineIdentifier->value())->first();

        if ($model === null) {
            return null;
        }

        return new RoutineExecutionDraft(
            new RoutineExecutionIdentifier($model->routine_execution_identifier),
            new AccountIdentifier($model->executor_account_identifier),
            new RoutineIdentifier($model->routine_identifier),
            new ExecutedAt($model->executed_at->toDateTimeImmutable()),
            $model->routine_execution_memo === null ? null : new RoutineExecutionMemo($model->routine_execution_memo),
        );

    }

    public function delete(RoutineExecutionDraft $routineExecutionDraft): void
    {
        RoutineExecutionDraftModel::query()->where('routine_execution_identifier', $routineExecutionDraft->routineExecutionIdentifier()->value())->delete();
    }

    public function save(RoutineExecutionDraft $routineExecutionDraft): void
    {
        try {
            RoutineExecutionDraftModel::query()->create([
                'routine_execution_identifier' => $routineExecutionDraft->routineExecutionIdentifier()->value(),
                'executor_account_identifier' => $routineExecutionDraft->executorAccountIdentifier()->value(),
                'routine_identifier' => $routineExecutionDraft->routineIdentifier()->value(),
                'executed_at' => $routineExecutionDraft->executedAt()->value(),
                'routine_execution_memo' => $routineExecutionDraft->routineExecutionMemo()?->value(),
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            if ($exception->index === 'execution_drafts_account_routine_unique' || ($exception->index === null && $exception->columns === ['executor_account_identifier', 'routine_identifier'])) {
                throw new RoutineExecutionDraftAlreadyExistsException(previous: $exception);
            }
            throw $exception;
        }
    }

    public function updateMemo(RoutineExecutionDraft $routineExecutionDraft): void
    {
        DB::transaction(function () use ($routineExecutionDraft): void {
            $query = DB::table('routine_execution_drafts')
                ->where('routine_execution_identifier', $routineExecutionDraft->routineExecutionIdentifier()->value())
                ->where('executor_account_identifier', $routineExecutionDraft->executorAccountIdentifier()->value());

            $affected = (clone $query)->update([
                'routine_execution_memo' => $routineExecutionDraft->routineExecutionMemo()?->value(),
            ]);

            if ($affected === 0 && (clone $query)->lockForUpdate()->first() === null) {
                throw new RoutineExecutionDraftNotFoundException;
            }
        });
    }
}
