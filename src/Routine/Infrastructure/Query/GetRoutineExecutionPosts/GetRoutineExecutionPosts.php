<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\GetRoutineExecutionPosts;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInputPort;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsOutput;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class GetRoutineExecutionPosts implements GetRoutineExecutionPostsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetRoutineExecutionPostsInputPort $input): GetRoutineExecutionPostsOutputPort
    {
        if (BlockVisibility::routineIsBlocked($input->viewerAccountIdentifier(), $input->routineIdentifier())) {
            throw new BlockedAccountVisibilityException;
        }

        $query = DB::table('posts')
            ->join('routine_executions', 'posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->join('routines', 'routine_executions.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
            ->join('accounts as routine_authors', 'routines.account_identifier', '=', 'routine_authors.account_identifier')
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
            ->orderBy('posts.post_identifier');

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier');
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'routine_authors.account_identifier');
        }
        PrivateAccountVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier', 'accounts.visibility');
        PrivateAccountVisibility::exclude($query, $input->viewerAccountIdentifier(), 'routine_authors.account_identifier', 'routine_authors.visibility');

        PostInteractionState::selectSupported($query, $input->viewerAccountIdentifier(), 'posts.post_identifier');

        $paginator = $query->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $items = $paginator->getCollection()
            ->map(fn (object $record): array => [
                'accountIdentifier' => (string) $record->account_identifier,
                'accountName' => (string) $record->account_name,
                'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $record->account_identifier)),
                'executedActionCount' => (int) $record->executed_action_count,
                'postedAt' => (new DateTimeImmutable((string) $record->posted_at))->format(DATE_ATOM),
                'routineExecutionIdentifier' => (string) $record->routine_execution_identifier,
                'routineExecutionMemo' => $record->routine_execution_memo === null ? null : (string) $record->routine_execution_memo,
                'supportCount' => (int) $record->post_support_count,
                'supported' => (bool) $record->supported,
            ])
            ->all();

        return new GetRoutineExecutionPostsOutput($items, $paginator->total());
    }
}
