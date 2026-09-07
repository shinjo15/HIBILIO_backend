<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetFavoriteTagPosts;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsInputPort;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsInterface;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsOutput;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsOutputPort;

final class GetFavoriteTagPosts implements GetFavoriteTagPostsInterface
{
    public function execute(GetFavoriteTagPostsInputPort $input): GetFavoriteTagPostsOutputPort
    {
        $paginator = DB::table('posts')
            ->join('routines', 'posts.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->whereIn('posts.routine_identifier', $this->favoriteTagRoutineIdentifiers($input->accountIdentifier()))
            ->where('routines.available', true)
            ->where('posts.available', true)
            ->where('posts.post_category', 'routine')
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->whereNotExists($this->blockExists($input->accountIdentifier()))
            ->select([
                'posts.post_identifier',
                'posts.routine_identifier',
                'posts.post_category',
                'posts.post_like_count',
                'posts.post_support_count',
                'posts.created_at as posted_at',
                'routines.account_identifier',
                'routines.routine_name',
                'routines.routine_execution_minutes',
                'accounts.account_name',
            ])
            ->selectSub($this->executionCount(), 'execution_count')
            ->selectSub($this->customizationCount(), 'customization_count')
            ->selectRaw($this->scoreExpression().' as recommendation_score')
            ->distinct()
            ->orderByDesc('recommendation_score')
            ->orderByDesc('posts.created_at')
            ->orderBy('posts.post_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $routineIdentifiers = $paginator->getCollection()
            ->pluck('routine_identifier')
            ->map(static fn (mixed $identifier): string => (string) $identifier)
            ->unique()
            ->values()
            ->all();

        $tagsByRoutineIdentifier = $this->tagsByRoutineIdentifier($routineIdentifiers);
        $actionsByRoutineIdentifier = $this->actionsByRoutineIdentifier($routineIdentifiers);

        $posts = $paginator->getCollection()
            ->map(static fn (object $record): array => [
                'postIdentifier' => (string) $record->post_identifier,
                'routineIdentifier' => (string) $record->routine_identifier,
                'postCategory' => (string) $record->post_category,
                'accountIdentifier' => (string) $record->account_identifier,
                'accountName' => (string) $record->account_name,
                'postedAt' => (new DateTimeImmutable((string) $record->posted_at))->format(DATE_ATOM),
                'routineName' => (string) $record->routine_name,
                'routineExecutionMinutes' => $record->routine_execution_minutes === null
                    ? null
                    : (int) $record->routine_execution_minutes,
                'tags' => $tagsByRoutineIdentifier[(string) $record->routine_identifier] ?? [],
                'routineActions' => $actionsByRoutineIdentifier[(string) $record->routine_identifier] ?? [],
                'postLikeCount' => (int) $record->post_like_count,
                'postSupportCount' => (int) $record->post_support_count,
                'executionCount' => (int) $record->execution_count,
                'customizationCount' => (int) $record->customization_count,
            ])
            ->values()
            ->all();

        return new GetFavoriteTagPostsOutput($posts, $paginator->total());
    }

    private function scoreExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "(posts.post_like_count + posts.post_support_count) * 0.5 - CAST((strftime('%s', 'now') - strftime('%s', posts.created_at)) / 3600 AS INTEGER)",
            'mysql' => '(posts.post_like_count + posts.post_support_count) * 0.5 - TIMESTAMPDIFF(HOUR, posts.created_at, NOW())',
            default => throw new \RuntimeException('未対応のデータベースドライバーです。'),
        };
    }

    private function favoriteTagRoutineIdentifiers(string $accountIdentifier): mixed
    {
        return DB::table('routine_tags')
            ->join('favorite_tags', 'routine_tags.tag_identifier', '=', 'favorite_tags.tag_identifier')
            ->where('favorite_tags.account_identifier', $accountIdentifier)
            ->where('routine_tags.available', true)
            ->select('routine_tags.routine_identifier');
    }

    private function blockExists(string $accountIdentifier): \Closure
    {
        return static function ($query) use ($accountIdentifier): void {
            $query->selectRaw('1')
                ->from('blocks')
                ->where(static function ($query) use ($accountIdentifier): void {
                    $query->where('blocks.blocking_account_identifier', $accountIdentifier)
                        ->whereColumn('blocks.blocked_account_identifier', 'routines.account_identifier');
                })
                ->orWhere(static function ($query) use ($accountIdentifier): void {
                    $query->where('blocks.blocked_account_identifier', $accountIdentifier)
                        ->whereColumn('blocks.blocking_account_identifier', 'routines.account_identifier');
                });
        };
    }

    private function executionCount(): mixed
    {
        return DB::table('posts as execution_posts')
            ->selectRaw('count(*)')
            ->whereColumn('execution_posts.routine_identifier', 'routines.routine_identifier')
            ->where('execution_posts.post_category', 'action')
            ->where('execution_posts.available', true);
    }

    private function customizationCount(): mixed
    {
        return DB::table('routines as customized_routines')
            ->selectRaw('count(*)')
            ->whereColumn('customized_routines.parent_routine_identifier', 'routines.routine_identifier')
            ->where('customized_routines.available', true);
    }

    /** @param list<string> $routineIdentifiers
     * @return array<string, list<array{tagIdentifier: string, tagName: string}>>
     */
    private function tagsByRoutineIdentifier(array $routineIdentifiers): array
    {
        if ($routineIdentifiers === []) {
            return [];
        }

        return DB::table('routine_tags')
            ->join('tags', 'routine_tags.tag_identifier', '=', 'tags.tag_identifier')
            ->whereIn('routine_tags.routine_identifier', $routineIdentifiers)
            ->where('routine_tags.available', true)
            ->where('tags.available', true)
            ->orderBy('tags.tag_identifier')
            ->get(['routine_tags.routine_identifier', 'tags.tag_identifier', 'tags.tag_name'])
            ->groupBy('routine_identifier')
            ->map(static fn ($tags): array => $tags->map(static fn (object $tag): array => [
                'tagIdentifier' => (string) $tag->tag_identifier,
                'tagName' => (string) $tag->tag_name,
            ])->values()->all())
            ->all();
    }

    /** @param list<string> $routineIdentifiers
     * @return array<string, list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>>
     */
    private function actionsByRoutineIdentifier(array $routineIdentifiers): array
    {
        if ($routineIdentifiers === []) {
            return [];
        }

        return DB::table('routine_actions')
            ->whereIn('routine_identifier', $routineIdentifiers)
            ->where('available', true)
            ->orderBy('created_at')
            ->orderBy('routine_action_identifier')
            ->get(['routine_identifier', 'routine_action_identifier', 'action_name', 'action_minutes'])
            ->groupBy('routine_identifier')
            ->map(static fn ($actions): array => $actions->map(static fn (object $action): array => [
                'routineActionIdentifier' => (string) $action->routine_action_identifier,
                'actionName' => (string) $action->action_name,
                'actionMinutes' => $action->action_minutes === null ? null : (int) $action->action_minutes,
            ])->values()->all())
            ->all();
    }
}
