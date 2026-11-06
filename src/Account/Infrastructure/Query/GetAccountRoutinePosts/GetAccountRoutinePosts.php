<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetAccountRoutinePosts;

use DateTimeImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInputPort;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInterface;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsOutput;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class GetAccountRoutinePosts implements GetAccountRoutinePostsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetAccountRoutinePostsInputPort $input): GetAccountRoutinePostsOutputPort
    {
        if ($input->viewerAccountIdentifier() !== null && BlockVisibility::exists($input->viewerAccountIdentifier(), $input->accountIdentifier())) {
            throw new BlockedAccountVisibilityException;
        }

        $firstPost = DB::table('posts as routine_posts')->select('routine_posts.post_identifier')
            ->whereColumn('routine_posts.routine_identifier', 'routines.routine_identifier')
            ->where('routine_posts.post_category', 'routine')->where('routine_posts.available', true)
            ->orderByRaw('CASE WHEN routine_posts.created_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('routine_posts.created_at')->orderBy('routine_posts.post_identifier')->limit(1);
        $query = DB::table('routines')->leftJoin('posts', static function (JoinClause $join) use ($firstPost): void {
            $join->on('posts.routine_identifier', '=', 'routines.routine_identifier')
                ->whereRaw('posts.post_identifier = ('.$firstPost->toSql().')', $firstPost->getBindings());
        });
        $query->orderByRaw('CASE WHEN COALESCE(posts.created_at, routines.created_at) IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('COALESCE(posts.created_at, routines.created_at) DESC')
            ->orderByRaw('CASE WHEN posts.post_identifier IS NULL THEN 1 ELSE 0 END')
            ->orderBy('posts.post_identifier')->orderBy('routines.routine_identifier');

        $query->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->where('routines.account_identifier', $input->accountIdentifier())
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->select([
                'posts.post_identifier', 'routines.routine_identifier', 'posts.post_like_count',
                'posts.post_support_count', 'posts.created_at as posted_at',
                'routines.account_identifier', 'routines.routine_name', 'routines.routine_execution_minutes',
                'accounts.account_name',
            ])
            ->selectSub($this->executionCount(), 'execution_count')
            ->selectSub($this->customizationCount(), 'customization_count');

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier');
        }
        PrivateAccountVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier', 'accounts.visibility');

        PostInteractionState::selectLiked($query, $input->viewerAccountIdentifier(), 'posts.post_identifier');

        $parents = DB::table('routines as parent_routines')->join('accounts as parent_accounts', 'parent_routines.account_identifier', '=', 'parent_accounts.account_identifier')
            ->where('parent_routines.available', true)->where('parent_accounts.available', true)->where('parent_accounts.status', 'active')
            ->select('parent_routines.routine_identifier', 'parent_routines.routine_name', 'parent_accounts.account_identifier', 'parent_accounts.account_name');
        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($parents, $input->viewerAccountIdentifier(), 'parent_accounts.account_identifier');
        }
        PrivateAccountVisibility::exclude($parents, $input->viewerAccountIdentifier(), 'parent_accounts.account_identifier', 'parent_accounts.visibility');
        $query->leftJoinSub($parents, 'origin', 'origin.routine_identifier', '=', 'routines.parent_routine_identifier')
            ->addSelect('origin.routine_identifier as origin_identifier', 'origin.routine_name as origin_name', 'origin.account_identifier as origin_author_identifier', 'origin.account_name as origin_author_name');

        $paginator = $query->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $routineIdentifiers = $paginator->getCollection()->pluck('routine_identifier')
            ->map(static fn (mixed $identifier): string => (string) $identifier)->unique()->values()->all();
        $tags = $this->tags($routineIdentifiers);
        $actions = $this->actions($routineIdentifiers);
        $icons = [];
        $icon = function (string $author) use (&$icons): ?string {
            if (! array_key_exists($author, $icons)) {
                $icons[$author] = $this->accountImageUrlService->iconImageUrl(new AccountIdentifier($author));
            }

            return $icons[$author];
        };
        $origin = function (object $row) use ($icon): ?array {
            if ($row->origin_identifier === null) {
                return null;
            }
            $author = (string) $row->origin_author_identifier;

            return ['routineIdentifier' => (string) $row->origin_identifier, 'routineName' => (string) $row->origin_name, 'accountName' => (string) $row->origin_author_name, 'iconImageUrl' => $icon($author)];
        };

        $items = $paginator->getCollection()->map(fn (object $post): array => [
            'postIdentifier' => $post->post_identifier === null ? null : (string) $post->post_identifier,
            'routineIdentifier' => (string) $post->routine_identifier,
            'customizationOrigin' => $origin($post),
            'accountIdentifier' => (string) $post->account_identifier,
            'accountName' => (string) $post->account_name,
            'iconImageUrl' => $icon((string) $post->account_identifier),
            'postedAt' => $post->posted_at === null ? null : (new DateTimeImmutable((string) $post->posted_at))->format(DATE_ATOM),
            'routineName' => (string) $post->routine_name,
            'routineExecutionMinutes' => $post->routine_execution_minutes === null ? null : (int) $post->routine_execution_minutes,
            'tags' => $tags[(string) $post->routine_identifier] ?? [],
            'routineActions' => $actions[(string) $post->routine_identifier] ?? [],
            'postLikeCount' => $post->post_identifier === null ? null : (int) $post->post_like_count,
            'postSupportCount' => $post->post_identifier === null ? null : (int) $post->post_support_count,
            'liked' => $post->post_identifier === null ? null : (bool) $post->liked,
            'executionCount' => (int) $post->execution_count,
            'customizationCount' => (int) $post->customization_count,
        ])->values()->all();

        return new GetAccountRoutinePostsOutput($items, $paginator->total());
    }

    private function executionCount(): mixed
    {
        return DB::table('posts as execution_posts')->selectRaw('count(*)')
            ->whereColumn('execution_posts.routine_identifier', 'routines.routine_identifier')
            ->where('execution_posts.post_category', 'action')->where('execution_posts.available', true);
    }

    private function customizationCount(): mixed
    {
        return DB::table('routines as customized_routines')->selectRaw('count(*)')
            ->whereColumn('customized_routines.parent_routine_identifier', 'routines.routine_identifier')
            ->where('customized_routines.available', true);
    }

    /** @param list<string> $routineIdentifiers @return array<string, list<array{tagIdentifier: string, tagName: string}>> */
    private function tags(array $routineIdentifiers): array
    {
        if ($routineIdentifiers === []) {
            return [];
        }

        return DB::table('routine_tags')->join('tags', 'routine_tags.tag_identifier', '=', 'tags.tag_identifier')
            ->whereIn('routine_tags.routine_identifier', $routineIdentifiers)->where('routine_tags.available', true)->where('tags.available', true)
            ->orderBy('tags.tag_identifier')->get(['routine_tags.routine_identifier', 'tags.tag_identifier', 'tags.tag_name'])
            ->groupBy('routine_identifier')->map(static fn ($tags): array => $tags->map(static fn (object $tag): array => ['tagIdentifier' => (string) $tag->tag_identifier, 'tagName' => (string) $tag->tag_name])->values()->all())->all();
    }

    /** @param list<string> $routineIdentifiers @return array<string, list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>> */
    private function actions(array $routineIdentifiers): array
    {
        if ($routineIdentifiers === []) {
            return [];
        }

        return DB::table('routine_actions')->whereIn('routine_identifier', $routineIdentifiers)->where('available', true)
            ->orderBy('created_at')->orderBy('routine_action_identifier')
            ->get(['routine_identifier', 'routine_action_identifier', 'action_name', 'action_minutes'])
            ->groupBy('routine_identifier')->map(static fn ($actions): array => $actions->map(static fn (object $action): array => ['routineActionIdentifier' => (string) $action->routine_action_identifier, 'actionName' => (string) $action->action_name, 'actionMinutes' => $action->action_minutes === null ? null : (int) $action->action_minutes])->values()->all())->all();
    }
}
