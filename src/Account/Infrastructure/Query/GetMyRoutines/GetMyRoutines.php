<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetMyRoutines;

use App\Models\PostModel;
use App\Models\RoutineModel;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetMyRoutines\GetMyRoutinesInputPort;
use Src\Account\Application\Usecase\Query\GetMyRoutines\GetMyRoutinesInterface;
use Src\Account\Application\Usecase\Query\GetMyRoutines\GetMyRoutinesOutput;
use Src\Account\Application\Usecase\Query\GetMyRoutines\GetMyRoutinesOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class GetMyRoutines implements GetMyRoutinesInterface
{
    public function __construct(private AccountImageUrlServiceInterface $images) {}

    public function execute(GetMyRoutinesInputPort $input): GetMyRoutinesOutputPort
    {
        $viewer = $input->accountIdentifier();
        $routinePosts = PostModel::query()->whereColumn('posts.routine_identifier', 'routines.routine_identifier')
            ->where('post_category', 'routine')->where('available', true)
            ->orderByRaw('CASE WHEN created_at IS NULL THEN 1 ELSE 0 END')->orderBy('created_at')->orderBy('post_identifier');
        $postedAt = (clone $routinePosts)->select('created_at')->limit(1);
        $postIdentifier = (clone $routinePosts)->select('post_identifier')->limit(1);
        $query = RoutineModel::query()->select('routines.*')->where('account_identifier', $viewer)->where('available', true)
            ->whereHas('account', static fn ($account) => $account->where('available', true)->where('status', 'active'))
            ->with([
                'account',
                'tags' => static fn ($tags) => $tags->wherePivot('available', true)->where('tags.available', true)->orderBy('tags.tag_identifier'),
                'actions' => static fn ($actions) => $actions->where('available', true)->orderBy('created_at')->orderBy('routine_action_identifier'),

                'parentRoutine' => static fn ($parents) => $parents->where('available', true)->whereHas('account', static function ($accounts) use ($viewer): void {
                    $accounts->where('available', true)->where('status', 'active');
                    BlockVisibility::exclude($accounts->getQuery(), $viewer, 'accounts.account_identifier');
                    PrivateAccountVisibility::exclude($accounts->getQuery(), $viewer, 'accounts.account_identifier', 'accounts.visibility');
                })->with('account'),
            ])
            ->withCount([
                'posts as execution_count' => static fn ($posts) => $posts->where('post_category', 'action')->where('available', true),
                'customizations as customization_count' => static fn ($routines) => $routines->where('available', true),
            ])
            ->selectSub($postIdentifier, 'routine_post_identifier')
            ->orderByRaw('CASE WHEN COALESCE(('.$postedAt->toSql().'), routines.created_at) IS NULL THEN 1 ELSE 0 END', $postedAt->getBindings())
            ->orderByRaw('COALESCE(('.$postedAt->toSql().'), routines.created_at) DESC', $postedAt->getBindings())
            ->orderByRaw('CASE WHEN ('.$postIdentifier->toSql().') IS NULL THEN 1 ELSE 0 END', $postIdentifier->getBindings())
            ->orderBy('routine_post_identifier')->orderBy('routine_identifier');

        $paginator = $query->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());
        $postIdentifiers = $paginator->getCollection()->pluck('routine_post_identifier')->filter()->values()->all();
        $posts = $postIdentifiers === [] ? collect() : PostModel::query()->whereIn('post_identifier', $postIdentifiers)
            ->withCount(['likes as viewer_likes_count' => static fn ($likes) => $likes->where('account_identifier', $viewer)])
            ->get()->keyBy('post_identifier');
        $icons = [];
        $icon = function (string $identifier) use (&$icons): ?string {
            if (! array_key_exists($identifier, $icons)) {
                $icons[$identifier] = $this->images->iconImageUrl(new AccountIdentifier($identifier));
            }

            return $icons[$identifier];
        };
        $items = $paginator->getCollection()->map(static function (RoutineModel $routine) use ($icon, $posts): array {
            $post = $posts->get($routine->routine_post_identifier);
            $parent = $routine->parentRoutine;

            return [
                'postIdentifier' => $post?->post_identifier,
                'routineIdentifier' => (string) $routine->routine_identifier,
                'customizationOrigin' => $parent === null ? null : [
                    'routineIdentifier' => (string) $parent->routine_identifier,
                    'routineName' => (string) $parent->routine_name,
                    'accountName' => (string) $parent->account->account_name,
                    'iconImageUrl' => $icon((string) $parent->account_identifier),
                ],
                'accountIdentifier' => (string) $routine->account_identifier,
                'accountName' => (string) $routine->account->account_name,
                'iconImageUrl' => $icon((string) $routine->account_identifier),
                'postedAt' => $post?->created_at?->toAtomString(),
                'routineName' => (string) $routine->routine_name,
                'routineExecutionMinutes' => $routine->routine_execution_minutes === null ? null : (int) $routine->routine_execution_minutes,
                'tags' => $routine->tags->map(static fn ($tag): array => ['tagIdentifier' => (string) $tag->tag_identifier, 'tagName' => (string) $tag->tag_name])->values()->all(),
                'routineActions' => $routine->actions->map(static fn ($action): array => ['routineActionIdentifier' => (string) $action->routine_action_identifier, 'actionName' => (string) $action->action_name, 'actionMinutes' => $action->action_minutes === null ? null : (int) $action->action_minutes])->values()->all(),
                'postLikeCount' => $post?->post_like_count,
                'postSupportCount' => $post?->post_support_count,
                'liked' => $post === null ? null : (bool) $post->viewer_likes_count,
                'executionCount' => (int) $routine->execution_count,
                'customizationCount' => (int) $routine->customization_count,
            ];
        })->values()->all();

        return new GetMyRoutinesOutput($items, $paginator->total());
    }
}
