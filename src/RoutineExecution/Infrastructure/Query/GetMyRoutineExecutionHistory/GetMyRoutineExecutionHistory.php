<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Query\GetMyRoutineExecutionHistory;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory\GetMyRoutineExecutionHistoryInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory\GetMyRoutineExecutionHistoryInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory\GetMyRoutineExecutionHistoryOutput;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory\GetMyRoutineExecutionHistoryOutputPort;
use Src\RoutineExecution\Infrastructure\Service\RoutineExecutionAccountIconUrl;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class GetMyRoutineExecutionHistory implements GetMyRoutineExecutionHistoryInterface
{
    public function __construct(private RoutineExecutionAccountIconUrl $accountIconUrl) {}

    public function execute(GetMyRoutineExecutionHistoryInputPort $input): GetMyRoutineExecutionHistoryOutputPort
    {
        $accountIdentifier = $input->accountIdentifier();
        $representativePost = DB::table('posts as candidates')
            ->select('candidates.post_identifier')
            ->whereColumn('candidates.routine_execution_identifier', 'routine_executions.routine_execution_identifier')
            ->whereColumn('candidates.routine_identifier', 'routine_executions.routine_identifier')
            ->where('candidates.post_category', 'action')
            ->where('candidates.available', true)
            ->orderByRaw('case when candidates.created_at is null then 1 else 0 end')
            ->orderBy('candidates.created_at')
            ->orderBy('candidates.post_identifier')
            ->limit(1);

        $query = DB::table('routine_executions')
            ->leftJoin('posts', fn ($join) => $join->where('posts.post_identifier', '=', $representativePost))
            ->join('routines', 'routine_executions.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
            ->join('accounts as routine_authors', 'routines.account_identifier', '=', 'routine_authors.account_identifier')
            ->where('routine_executions.executor_account_identifier', $accountIdentifier)
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->where('routine_executions.executed_at', '>=', $input->start())
            ->where('routine_executions.executed_at', '<', $input->end())
            ->select(['routine_executions.routine_execution_identifier', 'routine_executions.routine_identifier', 'routine_executions.routine_execution_memo', 'routine_executions.executed_at', 'routines.routine_name', 'accounts.account_identifier', 'accounts.account_name', 'posts.post_identifier', 'posts.created_at as posted_at', 'posts.post_support_count'])
            ->selectSub(DB::table('routine_execution_actions')->selectRaw('count(*)')->whereColumn('routine_execution_actions.routine_execution_identifier', 'routine_executions.routine_execution_identifier'), 'executed_action_count')
            ->orderByDesc('routine_executions.executed_at')
            ->orderBy('routine_executions.routine_execution_identifier');

        BlockVisibility::exclude($query, $accountIdentifier, 'accounts.account_identifier');
        BlockVisibility::exclude($query, $accountIdentifier, 'routine_authors.account_identifier');
        PrivateAccountVisibility::exclude($query, $accountIdentifier, 'accounts.account_identifier', 'accounts.visibility');
        PrivateAccountVisibility::exclude($query, $accountIdentifier, 'routine_authors.account_identifier', 'routine_authors.visibility');
        PostInteractionState::selectSupported($query, $accountIdentifier, 'posts.post_identifier');

        $records = $query->get();
        $iconImageUrl = $records->isEmpty() ? null : $this->accountIconUrl->forAccount($accountIdentifier);
        $items = $records->map(fn (object $record): array => [
            'routineExecutionIdentifier' => (string) $record->routine_execution_identifier,
            'routineIdentifier' => (string) $record->routine_identifier,
            'routineName' => (string) $record->routine_name,
            'accountIdentifier' => (string) $record->account_identifier,
            'accountName' => (string) $record->account_name,
            'iconImageUrl' => $iconImageUrl,
            'executedActionCount' => (int) $record->executed_action_count,
            'executedAt' => (new DateTimeImmutable((string) $record->executed_at, new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.uP'),
            'postedAt' => $record->posted_at === null ? null : (new DateTimeImmutable((string) $record->posted_at))->format(DATE_ATOM),
            'routineExecutionMemo' => $record->routine_execution_memo === null ? null : (string) $record->routine_execution_memo,
            'supportCount' => $record->post_identifier === null ? null : (int) $record->post_support_count,
            'supported' => $record->post_identifier === null ? null : (bool) $record->supported,
        ])->values()->all();

        return new GetMyRoutineExecutionHistoryOutput($items);
    }
}
