<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\GetRoutineDetails;

use Illuminate\Support\Facades\DB;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInputPort;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsOutput;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsOutputPort;

final class GetRoutineDetails implements GetRoutineDetailsInterface
{
    public function execute(GetRoutineDetailsInputPort $input): GetRoutineDetailsOutputPort
    {
        $routine = DB::table('routines')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->where('routines.routine_identifier', $input->routineIdentifier())
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->select([
                'routines.account_identifier',
                'accounts.account_name',
                'routines.routine_name',
                'routines.routine_memo',
                'routines.routine_execution_minutes',
            ])
            ->selectSub($this->executionCount(), 'execution_count')
            ->selectSub($this->customizationCount(), 'customization_count')
            ->selectSub($this->likeCount(), 'like_count')
            ->first();

        if ($routine === null) {
            return new GetRoutineDetailsOutput(null);
        }

        $routineActions = DB::table('routine_actions')
            ->where('routine_identifier', $input->routineIdentifier())
            ->where('available', true)
            ->orderBy('created_at')
            ->orderBy('routine_action_identifier')
            ->get([
                'routine_action_identifier',
                'action_name',
                'action_memo',
                'action_minutes',
            ])
            ->map(static fn (object $action): array => [
                'routineActionIdentifier' => (string) $action->routine_action_identifier,
                'actionName' => (string) $action->action_name,
                'actionMemo' => $action->action_memo === null ? null : (string) $action->action_memo,
                'actionMinutes' => $action->action_minutes === null ? null : (int) $action->action_minutes,
            ])
            ->all();

        return new GetRoutineDetailsOutput([
            'accountIdentifier' => (string) $routine->account_identifier,
            'accountName' => (string) $routine->account_name,
            'routineName' => (string) $routine->routine_name,
            'routineMemo' => $routine->routine_memo === null ? null : (string) $routine->routine_memo,
            'routineExecutionMinutes' => $routine->routine_execution_minutes === null ? null : (int) $routine->routine_execution_minutes,
            'executionCount' => (int) $routine->execution_count,
            'customizationCount' => (int) $routine->customization_count,
            'likeCount' => (int) $routine->like_count,
            'routineActions' => $routineActions,
        ]);
    }

    private function executionCount(): mixed
    {
        return DB::table('routine_executions')
            ->selectRaw('count(*)')
            ->whereColumn('routine_executions.routine_identifier', 'routines.routine_identifier');
    }

    private function customizationCount(): mixed
    {
        return DB::table('routines as customized_routines')
            ->selectRaw('count(*)')
            ->whereColumn('customized_routines.parent_routine_identifier', 'routines.routine_identifier')
            ->where('customized_routines.available', true);
    }

    private function likeCount(): mixed
    {
        return DB::table('posts')
            ->selectRaw('coalesce(sum(post_like_count), 0)')
            ->whereColumn('posts.routine_identifier', 'routines.routine_identifier')
            ->where('posts.available', true)
            ->where('posts.post_category', 'routine');
    }
}
