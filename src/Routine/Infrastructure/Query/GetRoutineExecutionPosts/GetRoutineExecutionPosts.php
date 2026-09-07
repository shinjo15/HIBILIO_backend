<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\GetRoutineExecutionPosts;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInputPort;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsOutput;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsOutputPort;

final class GetRoutineExecutionPosts implements GetRoutineExecutionPostsInterface
{
    public function execute(GetRoutineExecutionPostsInputPort $input): GetRoutineExecutionPostsOutputPort
    {
        $paginator = DB::table('posts')
            ->join('routine_executions', 'posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->join('routines', 'routine_executions.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
            ->where('posts.routine_identifier', $input->routineIdentifier())
            ->whereColumn('posts.routine_identifier', 'routine_executions.routine_identifier')
            ->where('posts.post_category', 'action')
            ->where('posts.available', true)
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->select([
                'accounts.account_identifier',
                'accounts.account_name',
                'routine_executions.routine_execution_identifier',
                'routine_executions.routine_execution_memo',
                'posts.created_at as posted_at',
                'posts.post_support_count',
            ])
            ->selectSub(
                DB::table('routine_execution_actions')
                    ->selectRaw('count(*)')
                    ->whereColumn('routine_execution_actions.routine_execution_identifier', 'routine_executions.routine_execution_identifier'),
                'executed_action_count',
            )
            ->orderByDesc('posts.created_at')
            ->orderBy('posts.post_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $items = $paginator->getCollection()
            ->map(static fn (object $record): array => [
                'accountIdentifier' => (string) $record->account_identifier,
                'accountName' => (string) $record->account_name,
                'executedActionCount' => (int) $record->executed_action_count,
                'postedAt' => (new DateTimeImmutable((string) $record->posted_at))->format(DATE_ATOM),
                'routineExecutionIdentifier' => (string) $record->routine_execution_identifier,
                'routineExecutionMemo' => $record->routine_execution_memo === null ? null : (string) $record->routine_execution_memo,
                'supportCount' => (int) $record->post_support_count,
            ])
            ->all();

        return new GetRoutineExecutionPostsOutput($items, $paginator->total());
    }
}
