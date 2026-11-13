<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\RoutineExecutionDraftModel;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
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

        $draft = new RoutineExecutionDraft(
            new RoutineExecutionIdentifier($model->routine_execution_identifier),
            new AccountIdentifier($model->executor_account_identifier),
            new RoutineIdentifier($model->routine_identifier),
            new ExecutedAt($model->executed_at->toDateTimeImmutable()),
            $model->routine_execution_memo === null ? null : new RoutineExecutionMemo($model->routine_execution_memo),
        );

        $actions = DB::table('routine_execution_action_drafts')
            ->where('routine_execution_identifier', $model->routine_execution_identifier)
            ->pluck('routine_action_identifier')
            ->map(static fn (string $id): RoutineExecutionAction => new RoutineExecutionAction(
                $draft->routineExecutionIdentifier(),
                new RoutineActionIdentifier($id),
            ))->all();
        $draft->changeActions($actions);

        return $draft;
    }

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

    public function updateAction(RoutineExecutionDraft $routineExecutionDraft, RoutineActionIdentifier $actionIdentifier): void
    {
        DB::transaction(function () use ($routineExecutionDraft, $actionIdentifier): void {
            $draft = DB::table('routine_execution_drafts')
                ->where('routine_execution_identifier', $routineExecutionDraft->routineExecutionIdentifier()->value())
                ->where('executor_account_identifier', $routineExecutionDraft->executorAccountIdentifier()->value())
                ->where('routine_identifier', $routineExecutionDraft->routineIdentifier()->value())
                ->lockForUpdate()->first();
            if ($draft === null) {
                throw new RoutineExecutionDraftNotFoundException;
            }

            DB::table('routine_execution_action_drafts')
                ->where('routine_execution_identifier', $routineExecutionDraft->routineExecutionIdentifier()->value())
                ->where('routine_action_identifier', $actionIdentifier->value())
                ->delete();
            if ($routineExecutionDraft->hasAction($actionIdentifier)) {
                DB::table('routine_execution_action_drafts')->insert([
                    'routine_execution_identifier' => $routineExecutionDraft->routineExecutionIdentifier()->value(),
                    'routine_action_identifier' => $actionIdentifier->value(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
