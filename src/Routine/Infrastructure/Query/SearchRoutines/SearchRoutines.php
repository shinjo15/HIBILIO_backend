<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\SearchRoutines;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesInputPort;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesInterface;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesOutput;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;
use Src\Shared\Infrastructure\Query\Post\PostInteractionState;

final class SearchRoutines implements SearchRoutinesInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(SearchRoutinesInputPort $input): SearchRoutinesOutputPort
    {
        $routineItems = DB::table('routines')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->select([
                DB::raw("'routine' as item_type"),
                'routines.routine_identifier',
                DB::raw('null as routine_execution_identifier'),
                'routines.routine_name',
                'accounts.account_identifier',
                'accounts.account_name',
                'routines.routine_execution_minutes',
                DB::raw('null as routine_execution_memo'),
                'routines.created_at as published_at',
            ])
            ->selectSub($this->routinePostField('created_at'), 'posted_at')
            ->selectSub($this->routinePostField('post_identifier'), 'post_identifier')
            ->selectSub($this->routinePostField('post_like_count'), 'post_like_count')
            ->selectRaw('null as post_support_count');
        $this->applySearchConditions($routineItems, $input, 'routines.account_identifier');

        $executionItems = DB::table('posts')
            ->join('routine_executions', 'posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->join('routines', 'posts.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
            ->join('accounts as routine_authors', 'routines.account_identifier', '=', 'routine_authors.account_identifier')
            ->whereColumn('posts.routine_identifier', 'routine_executions.routine_identifier')
            ->where('posts.post_category', 'action')
            ->where('posts.available', true)
            ->select([
                DB::raw("'routine_execution' as item_type"),
                'routines.routine_identifier',
                'routine_executions.routine_execution_identifier',
                'routines.routine_name',
                'accounts.account_identifier',
                'accounts.account_name',
                'routines.routine_execution_minutes',
                'routine_executions.routine_execution_memo',
                'posts.created_at as published_at',
                'posts.created_at as posted_at',
                'posts.post_identifier',
                'posts.post_like_count',
                'posts.post_support_count',
            ]);
        $this->applySearchConditions($executionItems, $input, 'routine_executions.executor_account_identifier');
        $this->applyRoutineAuthorSearchConditions($executionItems, $input);

        $searchItems = DB::query()
            ->fromSub($routineItems->unionAll($executionItems), 'search_items')
            ->select('search_items.*')
            ->orderByDesc('published_at')
            ->orderBy('item_type')
            ->orderBy('routine_identifier');
        PostInteractionState::selectBoth($searchItems, $input->accountIdentifier(), 'search_items.post_identifier');
        $paginator = $searchItems->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $routineIdentifiers = $paginator->getCollection()
            ->pluck('routine_identifier')
            ->map(static fn (mixed $identifier): string => (string) $identifier)
            ->unique()
            ->values()
            ->all();
        $tagsByRoutineIdentifier = $this->tagsByRoutineIdentifier($routineIdentifiers);
        $actionsByRoutineIdentifier = $this->actionsByRoutineIdentifier($routineIdentifiers);
        $metricsByRoutineIdentifier = $this->metricsByRoutineIdentifier($routineIdentifiers);
        $executionIdentifiers = $paginator->getCollection()
            ->pluck('routine_execution_identifier')
            ->filter()
            ->map(static fn (mixed $identifier): string => (string) $identifier)
            ->values()
            ->all();
        $executedActionCounts = $this->executedActionCounts($executionIdentifiers);

        $items = $paginator->getCollection()
            ->map(function (object $item) use ($actionsByRoutineIdentifier, $metricsByRoutineIdentifier, $tagsByRoutineIdentifier, $executedActionCounts): array {
                $routineIdentifier = (string) $item->routine_identifier;

                return [
                    'itemType' => (string) $item->item_type,
                    'routineIdentifier' => $routineIdentifier,
                    'routineExecutionIdentifier' => $item->routine_execution_identifier === null ? null : (string) $item->routine_execution_identifier,
                    'routineName' => (string) $item->routine_name,
                    'accountIdentifier' => (string) $item->account_identifier,
                    'accountName' => (string) $item->account_name,
                    'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $item->account_identifier)),
                    'tags' => $tagsByRoutineIdentifier[$routineIdentifier] ?? [],
                    'publishedAt' => (new DateTimeImmutable((string) $item->published_at))->format(DATE_ATOM),
                    'postedAt' => $item->posted_at === null ? null : (new DateTimeImmutable((string) $item->posted_at))->format(DATE_ATOM),
                    'postIdentifier' => $item->post_identifier === null ? null : (string) $item->post_identifier,
                    'routineExecutionMinutes' => $item->routine_execution_minutes === null ? null : (int) $item->routine_execution_minutes,
                    'routineActions' => $actionsByRoutineIdentifier[$routineIdentifier] ?? [],
                    'executionCount' => $metricsByRoutineIdentifier[$routineIdentifier]['executionCount'] ?? 0,
                    'customizationCount' => $metricsByRoutineIdentifier[$routineIdentifier]['customizationCount'] ?? 0,
                    ...$this->postFields($item, $executedActionCounts),
                ];
            })
            ->values()
            ->all();

        return new SearchRoutinesOutput($items, $paginator->total());
    }

    private function applySearchConditions(mixed $query, SearchRoutinesInputPort $input, string $accountIdentifierColumn): void
    {
        $query
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->where('accounts.visibility', AccountVisibility::PUBLIC->value);

        if ($input->title() !== null) {
            $query->where('routines.routine_name', 'like', '%'.$input->title().'%');
        }

        foreach ($input->tagIdentifiers() as $tagIdentifier) {
            $query->whereExists(static function ($query) use ($tagIdentifier): void {
                $query->selectRaw('1')
                    ->from('routine_tags')
                    ->whereColumn('routine_tags.routine_identifier', 'routines.routine_identifier')
                    ->where('routine_tags.tag_identifier', $tagIdentifier)
                    ->where('routine_tags.available', true);
            });
        }

        if ($input->accountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->accountIdentifier(), $accountIdentifierColumn);
        }
    }

    private function applyRoutineAuthorSearchConditions(mixed $query, SearchRoutinesInputPort $input): void
    {
        $query
            ->where('routine_authors.available', true)
            ->where('routine_authors.status', 'active')
            ->where('routine_authors.visibility', AccountVisibility::PUBLIC->value);

        if ($input->accountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->accountIdentifier(), 'routine_authors.account_identifier');
        }
    }

    private function routinePostField(string $field): mixed
    {
        return DB::table('posts as routine_posts')
            ->select("routine_posts.{$field}")
            ->whereColumn('routine_posts.routine_identifier', 'routines.routine_identifier')
            ->where('routine_posts.post_category', 'routine')
            ->where('routine_posts.available', true)
            ->orderBy('routine_posts.created_at')
            ->orderBy('routine_posts.post_identifier')
            ->limit(1);
    }

    /** @param array<string, int> $executedActionCounts */
    private function postFields(object $item, array $executedActionCounts): array
    {
        if ($item->item_type === 'routine') {
            return [
                'postLikeCount' => $item->post_identifier === null ? null : (int) $item->post_like_count,
                'liked' => $item->post_identifier === null ? null : (bool) $item->liked,
            ];
        }

        return [
            'postLikeCount' => (int) $item->post_like_count,
            'supportCount' => (int) $item->post_support_count,
            'supported' => (bool) $item->supported,
            'executedActionCount' => $executedActionCounts[(string) $item->routine_execution_identifier] ?? 0,
            'routineExecutionMemo' => $item->routine_execution_memo === null ? null : (string) $item->routine_execution_memo,
        ];
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

    /** @param list<string> $routineIdentifiers
     * @return array<string, array{executionCount: int, customizationCount: int}>
     */
    private function metricsByRoutineIdentifier(array $routineIdentifiers): array
    {
        if ($routineIdentifiers === []) {
            return [];
        }

        return DB::table('routines')
            ->whereIn('routine_identifier', $routineIdentifiers)
            ->select('routine_identifier')
            ->selectSub(DB::table('posts as execution_posts')->selectRaw('count(*)')
                ->whereColumn('execution_posts.routine_identifier', 'routines.routine_identifier')
                ->where('execution_posts.post_category', 'action')
                ->where('execution_posts.available', true), 'execution_count')
            ->selectSub(DB::table('routines as customized_routines')->selectRaw('count(*)')
                ->whereColumn('customized_routines.parent_routine_identifier', 'routines.routine_identifier')
                ->where('customized_routines.available', true), 'customization_count')
            ->get()
            ->mapWithKeys(static fn (object $metric): array => [(string) $metric->routine_identifier => [
                'executionCount' => (int) $metric->execution_count,
                'customizationCount' => (int) $metric->customization_count,
            ]])
            ->all();
    }

    /** @param list<string> $executionIdentifiers
     * @return array<string, int>
     */
    private function executedActionCounts(array $executionIdentifiers): array
    {
        if ($executionIdentifiers === []) {
            return [];
        }

        return DB::table('routine_execution_actions')
            ->whereIn('routine_execution_identifier', $executionIdentifiers)
            ->selectRaw('routine_execution_identifier, count(*) as executed_action_count')
            ->groupBy('routine_execution_identifier')
            ->pluck('executed_action_count', 'routine_execution_identifier')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * @param  list<string>  $routineIdentifiers
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
}
