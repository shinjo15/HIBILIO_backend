<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetMyPosts;

use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetMyPosts\GetMyPostsInputPort;
use Src\Account\Application\Usecase\Query\GetMyPosts\GetMyPostsInterface;
use Src\Account\Application\Usecase\Query\GetMyPosts\GetMyPostsOutput;
use Src\Account\Application\Usecase\Query\GetMyPosts\GetMyPostsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class GetMyPosts implements GetMyPostsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetMyPostsInputPort $input): GetMyPostsOutputPort
    {
        $viewer = $input->viewerAccountIdentifier();
        if ($viewer !== null && $viewer !== $input->accountIdentifier() && BlockVisibility::exists($viewer, $input->accountIdentifier())) {
            throw new BlockedAccountVisibilityException;
        }
        $query = DB::table('posts')
            ->join('routines', 'posts.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts as routine_authors', 'routines.account_identifier', '=', 'routine_authors.account_identifier')
            ->leftJoin('routine_executions', static function (JoinClause $join): void {
                $join->on('posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
                    ->on('routine_executions.routine_identifier', '=', 'posts.routine_identifier');
            })
            ->join('accounts', static function (JoinClause $join): void {
                $join->on('accounts.account_identifier', '=', DB::raw("CASE WHEN posts.post_category = 'action' THEN routine_executions.executor_account_identifier ELSE routines.account_identifier END"));
            })
            ->where('accounts.account_identifier', $input->accountIdentifier())
            ->where('accounts.available', true)->where('accounts.status', 'active')
            ->where('routine_authors.available', true)->where('routine_authors.status', 'active')
            ->where('routines.available', true)->where('posts.available', true)
            ->where(static function (Builder $query): void {
                $query->where(static fn (Builder $routine) => $routine->where('posts.post_category', 'routine')->whereNull('posts.routine_execution_identifier'))
                    ->orWhere(static fn (Builder $action) => $action->where('posts.post_category', 'action')->whereNotNull('routine_executions.routine_execution_identifier'));
            })
            ->select(['posts.post_identifier', 'posts.routine_identifier', 'posts.routine_execution_identifier', 'posts.post_category', 'posts.post_like_count', 'posts.post_support_count', 'posts.created_at as posted_at', 'routine_executions.routine_execution_memo', 'accounts.account_identifier', 'accounts.account_name', 'routines.routine_name', 'routines.routine_execution_minutes'])
            ->selectSub(DB::table('routine_execution_actions')->selectRaw('count(*)')->whereColumn('routine_execution_actions.routine_execution_identifier', 'routine_executions.routine_execution_identifier'), 'executed_action_count')
            ->selectSub(DB::table('posts as execution_posts')->selectRaw('count(*)')->whereColumn('execution_posts.routine_identifier', 'routines.routine_identifier')->where('execution_posts.post_category', 'action')->where('execution_posts.available', true), 'execution_count')
            ->selectSub(DB::table('routines as customized_routines')->selectRaw('count(*)')->whereColumn('customized_routines.parent_routine_identifier', 'routines.routine_identifier')->where('customized_routines.available', true), 'customization_count')
            ->orderByRaw('CASE WHEN posts.created_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('posts.created_at')->orderBy('posts.post_identifier');

        if ($viewer !== null) {
            BlockVisibility::exclude($query, $viewer, 'routine_authors.account_identifier');
        }
        PrivateAccountVisibility::exclude($query, $viewer, 'accounts.account_identifier', 'accounts.visibility');
        PrivateAccountVisibility::exclude($query, $viewer, 'routine_authors.account_identifier', 'routine_authors.visibility');
        PostInteractionState::selectBoth($query, $viewer, 'posts.post_identifier');

        $parents = DB::table('routines as parent_routines')->join('accounts as parent_accounts', 'parent_routines.account_identifier', '=', 'parent_accounts.account_identifier')
            ->where('parent_routines.available', true)->where('parent_accounts.available', true)->where('parent_accounts.status', 'active')
            ->select('parent_routines.routine_identifier', 'parent_routines.routine_name', 'parent_accounts.account_identifier', 'parent_accounts.account_name');
        if ($viewer !== null) {
            BlockVisibility::exclude($parents, $viewer, 'parent_accounts.account_identifier');
        }
        PrivateAccountVisibility::exclude($parents, $viewer, 'parent_accounts.account_identifier', 'parent_accounts.visibility');
        $query->leftJoinSub($parents, 'origin', 'origin.routine_identifier', '=', 'routines.parent_routine_identifier')
            ->addSelect('origin.routine_identifier as origin_identifier', 'origin.routine_name as origin_name', 'origin.account_identifier as origin_author_identifier', 'origin.account_name as origin_author_name');

        $paginator = $query->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());
        $identifiers = $paginator->getCollection()->pluck('routine_identifier')->map(static fn (mixed $id): string => (string) $id)->unique()->values()->all();
        $tags = $identifiers === [] ? collect() : DB::table('routine_tags')->join('tags', 'routine_tags.tag_identifier', '=', 'tags.tag_identifier')
            ->whereIn('routine_tags.routine_identifier', $identifiers)->where('routine_tags.available', true)->where('tags.available', true)
            ->orderBy('tags.tag_identifier')->get(['routine_tags.routine_identifier', 'tags.tag_identifier', 'tags.tag_name'])->groupBy('routine_identifier');
        $actions = $identifiers === [] ? collect() : DB::table('routine_actions')->whereIn('routine_identifier', $identifiers)->where('available', true)
            ->orderBy('created_at')->orderBy('routine_action_identifier')->get(['routine_identifier', 'routine_action_identifier', 'action_name', 'action_minutes'])->groupBy('routine_identifier');
        $icons = [];
        $icon = function (string $author) use (&$icons): ?string {
            if (! array_key_exists($author, $icons)) {
                $icons[$author] = $this->accountImageUrlService->iconImageUrl(new AccountIdentifier($author));
            }

            return $icons[$author];
        };

        $items = $paginator->getCollection()->map(static function (object $post) use ($tags, $actions, $icon): array {
            $origin = $post->post_category === 'routine' && $post->origin_identifier !== null
                ? ['routineIdentifier' => (string) $post->origin_identifier, 'routineName' => (string) $post->origin_name, 'accountName' => (string) $post->origin_author_name, 'iconImageUrl' => $icon((string) $post->origin_author_identifier)] : null;

            return [
                'postIdentifier' => (string) $post->post_identifier,
                'routineIdentifier' => (string) $post->routine_identifier,
                'routineExecutionIdentifier' => $post->routine_execution_identifier === null ? null : (string) $post->routine_execution_identifier,
                'postCategory' => (string) $post->post_category,
                'accountIdentifier' => (string) $post->account_identifier,
                'accountName' => (string) $post->account_name,
                'iconImageUrl' => $icon((string) $post->account_identifier),
                'postedAt' => $post->posted_at === null ? null : (new DateTimeImmutable((string) $post->posted_at))->format(DATE_ATOM),
                'routineName' => (string) $post->routine_name,
                'routineExecutionMinutes' => $post->routine_execution_minutes === null ? null : (int) $post->routine_execution_minutes,
                'tags' => ($tags[(string) $post->routine_identifier] ?? collect())->map(static fn (object $tag): array => ['tagIdentifier' => (string) $tag->tag_identifier, 'tagName' => (string) $tag->tag_name])->values()->all(),
                'routineActions' => ($actions[(string) $post->routine_identifier] ?? collect())->map(static fn (object $action): array => ['routineActionIdentifier' => (string) $action->routine_action_identifier, 'actionName' => (string) $action->action_name, 'actionMinutes' => $action->action_minutes === null ? null : (int) $action->action_minutes])->values()->all(),
                'postLikeCount' => (int) $post->post_like_count,
                'supportCount' => (int) $post->post_support_count,
                'liked' => (bool) $post->liked,
                'supported' => (bool) $post->supported,
                'executedActionCount' => (int) $post->executed_action_count,
                'routineExecutionMemo' => $post->routine_execution_memo === null ? null : (string) $post->routine_execution_memo,
                'executionCount' => (int) $post->execution_count,
                'customizationCount' => (int) $post->customization_count,
                'customizationOrigin' => $origin,
            ];
        })->values()->all();

        return new GetMyPostsOutput($items, $paginator->total());
    }
}
