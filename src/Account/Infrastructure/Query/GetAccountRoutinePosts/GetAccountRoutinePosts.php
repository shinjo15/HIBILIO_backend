<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetAccountRoutinePosts;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInputPort;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInterface;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsOutput;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class GetAccountRoutinePosts implements GetAccountRoutinePostsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetAccountRoutinePostsInputPort $input): GetAccountRoutinePostsOutputPort
    {
        if ($input->viewerAccountIdentifier() !== null && BlockVisibility::exists($input->viewerAccountIdentifier(), $input->accountIdentifier())) {
            throw new BlockedAccountVisibilityException;
        }

        $query = DB::table('posts')
            ->join('routines', 'posts.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->where('routines.account_identifier', $input->accountIdentifier())
            ->where('posts.post_category', 'routine')
            ->where('posts.available', true)
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->select([
                'posts.post_identifier', 'posts.routine_identifier', 'posts.post_like_count',
                'posts.post_support_count', 'posts.created_at as posted_at',
                'routines.account_identifier', 'routines.routine_name', 'routines.routine_execution_minutes',
                'accounts.account_name',
            ])
            ->selectSub($this->executionCount(), 'execution_count')
            ->selectSub($this->customizationCount(), 'customization_count')
            ->orderByDesc('posts.created_at')
            ->orderBy('posts.post_identifier');

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier');
        }

        $paginator = $query->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $routineIdentifiers = $paginator->getCollection()->pluck('routine_identifier')
            ->map(static fn (mixed $identifier): string => (string) $identifier)->unique()->values()->all();
        $tags = $this->tags($routineIdentifiers);
        $actions = $this->actions($routineIdentifiers);

        $items = $paginator->getCollection()->map(fn (object $post): array => [
            'postIdentifier' => (string) $post->post_identifier,
            'routineIdentifier' => (string) $post->routine_identifier,
            'accountIdentifier' => (string) $post->account_identifier,
            'accountName' => (string) $post->account_name,
            'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $post->account_identifier)),
            'postedAt' => (new DateTimeImmutable((string) $post->posted_at))->format(DATE_ATOM),
            'routineName' => (string) $post->routine_name,
            'routineExecutionMinutes' => $post->routine_execution_minutes === null ? null : (int) $post->routine_execution_minutes,
            'tags' => $tags[(string) $post->routine_identifier] ?? [],
            'routineActions' => $actions[(string) $post->routine_identifier] ?? [],
            'postLikeCount' => (int) $post->post_like_count,
            'postSupportCount' => (int) $post->post_support_count,
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
