<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Query\GetAccountRoutineExecutions;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsOutput;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class GetAccountRoutineExecutions implements GetAccountRoutineExecutionsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetAccountRoutineExecutionsInputPort $input): GetAccountRoutineExecutionsOutputPort
    {
        if ($input->viewerAccountIdentifier() !== null && BlockVisibility::exists($input->viewerAccountIdentifier(), $input->accountIdentifier())) {
            throw new BlockedAccountVisibilityException;
        }

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
            ->where('routine_executions.executor_account_identifier', $input->accountIdentifier())
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->select(['routine_executions.routine_execution_identifier', 'routine_executions.routine_identifier', 'routine_executions.routine_execution_memo', 'routines.routine_name', 'accounts.account_identifier', 'accounts.account_name', 'posts.post_identifier', 'posts.created_at as posted_at', 'posts.post_support_count'])
            ->selectSub(DB::table('routine_execution_actions')->selectRaw('count(*)')->whereColumn('routine_execution_actions.routine_execution_identifier', 'routine_executions.routine_execution_identifier'), 'executed_action_count')
            ->orderByRaw('case when coalesce(posts.created_at, routine_executions.executed_at) is null then 1 else 0 end')
            ->orderByRaw('coalesce(posts.created_at, routine_executions.executed_at) desc')
            ->orderByRaw('case when posts.post_identifier is null then 1 else 0 end')
            ->orderBy('posts.post_identifier')
            ->orderBy('routine_executions.routine_execution_identifier');

        if ($input->viewerAccountIdentifier() !== $input->accountIdentifier()) {
            $query->where(function ($visibility): void {
                $visibility->whereNotNull('posts.post_identifier')
                    ->orWhereNotExists(function ($posts): void {
                        $posts->selectRaw('1')->from('posts as matching_posts')
                            ->whereColumn('matching_posts.routine_execution_identifier', 'routine_executions.routine_execution_identifier')
                            ->whereColumn('matching_posts.routine_identifier', 'routine_executions.routine_identifier')
                            ->where('matching_posts.post_category', 'action');
                    });
            });
        }

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier');
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'routine_authors.account_identifier');
        }
        PrivateAccountVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier', 'accounts.visibility');
        PrivateAccountVisibility::exclude($query, $input->viewerAccountIdentifier(), 'routine_authors.account_identifier', 'routine_authors.visibility');

        PostInteractionState::selectSupported($query, $input->viewerAccountIdentifier(), 'posts.post_identifier');

        $paginator = $query->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $iconImageUrl = $paginator->isEmpty() ? null : $this->accountImageUrlService->iconImageUrl(new AccountIdentifier($input->accountIdentifier()));
        $items = $paginator->getCollection()->map(fn (object $record): array => [
            'routineExecutionIdentifier' => (string) $record->routine_execution_identifier,
            'routineIdentifier' => (string) $record->routine_identifier,
            'routineName' => (string) $record->routine_name,
            'accountIdentifier' => (string) $record->account_identifier,
            'accountName' => (string) $record->account_name,
            'iconImageUrl' => $iconImageUrl,
            'executedActionCount' => (int) $record->executed_action_count,
            'postedAt' => $record->posted_at === null ? null : (new DateTimeImmutable((string) $record->posted_at))->format(DATE_ATOM),
            'routineExecutionMemo' => $record->routine_execution_memo === null ? null : (string) $record->routine_execution_memo,
            'supportCount' => $record->post_identifier === null ? null : (int) $record->post_support_count,
            'supported' => $record->post_identifier === null ? null : (bool) $record->supported,
        ])->values()->all();

        return new GetAccountRoutineExecutionsOutput($items, $paginator->total());
    }
}
