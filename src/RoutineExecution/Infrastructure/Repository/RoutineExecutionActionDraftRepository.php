<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionDraftRepositoryInterface;

final class RoutineExecutionActionDraftRepository implements RoutineExecutionActionDraftRepositoryInterface
{
    public function save(RoutineExecutionAction $action, RoutineExecutionDraft $draft): void
    {
        $this->change($action, $draft, true);
    }

    public function delete(RoutineExecutionAction $action, RoutineExecutionDraft $draft): void
    {
        $this->change($action, $draft, false);
    }

    private function change(RoutineExecutionAction $action, RoutineExecutionDraft $draft, bool $save): void
    {
        if ($action->routineExecutionIdentifier()->value() !== $draft->routineExecutionIdentifier()->value()) {
            throw new \InvalidArgumentException('実行記録が一致しません。');
        }

        DB::transaction(function () use ($action, $draft, $save): void {
            $parent = DB::table('routine_execution_drafts')
                ->where('routine_execution_identifier', $draft->routineExecutionIdentifier()->value())
                ->where('executor_account_identifier', $draft->executorAccountIdentifier()->value())
                ->where('routine_identifier', $draft->routineIdentifier()->value())
                ->lockForUpdate()->first();
            if ($parent === null) {
                throw new RoutineExecutionDraftNotFoundException;
            }

            $values = [
                'routine_execution_identifier' => $action->routineExecutionIdentifier()->value(),
                'routine_action_identifier' => $action->routineActionIdentifier()->value(),
            ];
            DB::table('routine_execution_action_drafts')->where($values)->delete();
            if ($save) {
                DB::table('routine_execution_action_drafts')->insert($values + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
