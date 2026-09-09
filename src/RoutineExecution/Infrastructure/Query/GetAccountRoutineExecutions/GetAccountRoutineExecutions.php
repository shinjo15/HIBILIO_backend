<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Query\GetAccountRoutineExecutions;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsOutput;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsOutputPort;

final class GetAccountRoutineExecutions implements GetAccountRoutineExecutionsInterface
{
    public function execute(GetAccountRoutineExecutionsInputPort $input): GetAccountRoutineExecutionsOutputPort
    {
        $paginator = DB::table('posts')
            ->join('routine_executions', 'posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->join('routines', 'routine_executions.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
            ->where('routine_executions.executor_account_identifier', $input->accountIdentifier())
            ->whereColumn('posts.routine_identifier', 'routine_executions.routine_identifier')
            ->where('posts.post_category', 'action')
            ->where('posts.available', true)
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->select(['routine_executions.routine_execution_identifier', 'routine_executions.routine_identifier', 'routine_executions.routine_execution_memo', 'routines.routine_name', 'posts.created_at as posted_at', 'posts.post_support_count'])
            ->selectSub(DB::table('routine_execution_actions')->selectRaw('count(*)')->whereColumn('routine_execution_actions.routine_execution_identifier', 'routine_executions.routine_execution_identifier'), 'executed_action_count')
            ->orderByDesc('posts.created_at')->orderBy('posts.post_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $items = $paginator->getCollection()->map(static fn (object $record): array => [
            'routineExecutionIdentifier' => (string) $record->routine_execution_identifier,
            'routineIdentifier' => (string) $record->routine_identifier,
            'routineName' => (string) $record->routine_name,
            'executedActionCount' => (int) $record->executed_action_count,
            'postedAt' => (new DateTimeImmutable((string) $record->posted_at))->format(DATE_ATOM),
            'routineExecutionMemo' => $record->routine_execution_memo === null ? null : (string) $record->routine_execution_memo,
            'supportCount' => (int) $record->post_support_count,
        ])->values()->all();

        return new GetAccountRoutineExecutionsOutput($items, $paginator->total());
    }
}
