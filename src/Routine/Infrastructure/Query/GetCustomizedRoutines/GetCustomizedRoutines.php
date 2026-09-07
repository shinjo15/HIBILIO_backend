<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\GetCustomizedRoutines;

use Illuminate\Support\Facades\DB;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInputPort;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInterface;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesOutput;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesOutputPort;

final class GetCustomizedRoutines implements GetCustomizedRoutinesInterface
{
    public function execute(GetCustomizedRoutinesInputPort $input): GetCustomizedRoutinesOutputPort
    {
        $parentRoutineExists = DB::table('routines')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->where('routines.routine_identifier', $input->parentRoutineIdentifier())
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->exists();

        if (! $parentRoutineExists) {
            return new GetCustomizedRoutinesOutput([], 0, false);
        }

        $paginator = DB::table('routines')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->where('routines.parent_routine_identifier', $input->parentRoutineIdentifier())
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
            ->orderByDesc('routines.created_at')
            ->orderBy('routines.routine_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $items = $paginator->getCollection()
            ->map(static fn (object $routine): array => [
                'accountIdentifier' => (string) $routine->account_identifier,
                'accountName' => (string) $routine->account_name,
                'routineName' => (string) $routine->routine_name,
                'routineMemo' => $routine->routine_memo === null ? null : (string) $routine->routine_memo,
                'routineExecutionMinutes' => $routine->routine_execution_minutes === null ? null : (int) $routine->routine_execution_minutes,
                'executionCount' => (int) $routine->execution_count,
                'customizationCount' => (int) $routine->customization_count,
                'likeCount' => (int) $routine->like_count,
            ])
            ->all();

        return new GetCustomizedRoutinesOutput($items, $paginator->total(), true);
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
