<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Query\GetRecentExecutedRoutines;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines\GetRecentExecutedRoutinesInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class GetRecentExecutedRoutines implements GetRecentExecutedRoutinesInterface
{
    public function __construct(private AccountImageUrlServiceInterface $images) {}

    public function execute(string $accountIdentifier, int $page, int $numberOfItemsPerPage): array
    {
        $executions = DB::table('routine_executions')->select('routine_identifier')
            ->selectRaw('count(*) as my_execution_count, max(executed_at) as last_executed_at')
            ->where('executor_account_identifier', $accountIdentifier)->groupBy('routine_identifier');

        $post = DB::table('posts as routine_posts')->select('routine_posts.post_identifier')
            ->whereColumn('routine_posts.routine_identifier', 'routines.routine_identifier')
            ->where('routine_posts.post_category', 'routine')->where('routine_posts.available', true)
            ->orderByRaw('case when routine_posts.created_at is null then 1 else 0 end')
            ->orderBy('routine_posts.created_at')->orderBy('routine_posts.post_identifier')->limit(1);

        $parents = DB::table('routines as parent_routines')
            ->join('accounts as parent_accounts', 'parent_routines.account_identifier', '=', 'parent_accounts.account_identifier')
            ->where('parent_routines.available', true)->where('parent_accounts.available', true)->where('parent_accounts.status', 'active')
            ->select('parent_routines.routine_identifier', 'parent_routines.routine_name', 'parent_accounts.account_identifier', 'parent_accounts.account_name');
        BlockVisibility::exclude($parents, $accountIdentifier, 'parent_accounts.account_identifier');
        PrivateAccountVisibility::exclude($parents, $accountIdentifier, 'parent_accounts.account_identifier', 'parent_accounts.visibility');

        $query = DB::query()->fromSub($executions, 'executions')
            ->join('routines', 'executions.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->leftJoin('posts', static function ($join) use ($post): void {
                $join->whereRaw('posts.post_identifier = ('.$post->toSql().')', $post->getBindings());
            })
            ->leftJoinSub($parents, 'origin', 'origin.routine_identifier', '=', 'routines.parent_routine_identifier')
            ->where('routines.available', true)->where('accounts.available', true)->where('accounts.status', 'active')
            ->select('executions.*', 'routines.account_identifier', 'routines.routine_name', 'routines.routine_execution_minutes', 'accounts.account_name',
                'posts.post_identifier', 'posts.created_at as posted_at', 'posts.post_like_count', 'posts.post_support_count',
                'origin.routine_identifier as origin_identifier', 'origin.routine_name as origin_name', 'origin.account_identifier as origin_author_identifier', 'origin.account_name as origin_author_name')
            ->selectSub(DB::table('posts as execution_posts')->selectRaw('count(*)')->whereColumn('execution_posts.routine_identifier', 'routines.routine_identifier')->where('execution_posts.post_category', 'action')->where('execution_posts.available', true), 'execution_count')
            ->selectSub(DB::table('routines as customized_routines')->selectRaw('count(*)')->whereColumn('customized_routines.parent_routine_identifier', 'routines.routine_identifier')->where('customized_routines.available', true), 'customization_count')
            ->orderByDesc('executions.last_executed_at')->orderBy('executions.routine_identifier');
        BlockVisibility::exclude($query, $accountIdentifier, 'accounts.account_identifier');
        PrivateAccountVisibility::exclude($query, $accountIdentifier, 'accounts.account_identifier', 'accounts.visibility');
        PostInteractionState::selectLiked($query, $accountIdentifier, 'posts.post_identifier');
        $paginator = $query->paginate($numberOfItemsPerPage, ['*'], 'page', $page);
        $ids = $paginator->getCollection()->pluck('routine_identifier')->all();
        $tags = DB::table('routine_tags')->join('tags', 'routine_tags.tag_identifier', '=', 'tags.tag_identifier')
            ->whereIn('routine_tags.routine_identifier', $ids)->where('routine_tags.available', true)->where('tags.available', true)
            ->orderBy('tags.tag_identifier')->get(['routine_tags.routine_identifier', 'tags.tag_identifier', 'tags.tag_name'])->groupBy('routine_identifier');
        $actions = DB::table('routine_actions')->whereIn('routine_identifier', $ids)->where('available', true)
            ->orderBy('created_at')->orderBy('routine_action_identifier')
            ->get(['routine_identifier', 'routine_action_identifier', 'action_name', 'action_minutes'])->groupBy('routine_identifier');
        $icons = [];
        $icon = function (string $id) use (&$icons): ?string {
            if (! array_key_exists($id, $icons)) {
                $icons[$id] = $this->images->iconImageUrl(new AccountIdentifier($id));
            }

            return $icons[$id];
        };
        $items = $paginator->getCollection()->map(static function (object $row) use ($tags, $actions, $icon): array {
            $origin = $row->origin_identifier === null ? null : [
                'routine_identifier' => (string) $row->origin_identifier, 'routine_name' => (string) $row->origin_name,
                'account_name' => (string) $row->origin_author_name, 'icon_image_url' => $icon((string) $row->origin_author_identifier),
            ];

            return [
                'post_identifier' => $row->post_identifier === null ? null : (string) $row->post_identifier,
                'routine_identifier' => (string) $row->routine_identifier,
                'customization_origin' => $origin,
                'account_identifier' => (string) $row->account_identifier,
                'account_name' => (string) $row->account_name,
                'icon_image_url' => $icon((string) $row->account_identifier),
                'posted_at' => $row->posted_at === null ? null : (new DateTimeImmutable((string) $row->posted_at))->format(DATE_ATOM),
                'routine_name' => (string) $row->routine_name,
                'routine_execution_minutes' => $row->routine_execution_minutes === null ? null : (int) $row->routine_execution_minutes,
                'tags' => ($tags[$row->routine_identifier] ?? collect())->map(static fn (object $tag): array => ['tag_identifier' => (string) $tag->tag_identifier, 'tag_name' => (string) $tag->tag_name])->values()->all(),
                'routine_actions' => ($actions[$row->routine_identifier] ?? collect())->map(static fn (object $action): array => ['routine_action_identifier' => (string) $action->routine_action_identifier, 'action_name' => (string) $action->action_name, 'action_minutes' => $action->action_minutes === null ? null : (int) $action->action_minutes])->values()->all(),
                'post_like_count' => $row->post_like_count === null ? null : (int) $row->post_like_count,
                'post_support_count' => $row->post_support_count === null ? null : (int) $row->post_support_count,
                'liked' => $row->post_identifier === null ? null : (bool) $row->liked,
                'execution_count' => (int) $row->execution_count,
                'customization_count' => (int) $row->customization_count,
                'my_execution_count' => (int) $row->my_execution_count,
                'last_executed_at' => (new DateTimeImmutable((string) $row->last_executed_at))->format(DATE_ATOM),
            ];
        })->values()->all();

        return ['items' => $items, 'total' => $paginator->total()];
    }
}
