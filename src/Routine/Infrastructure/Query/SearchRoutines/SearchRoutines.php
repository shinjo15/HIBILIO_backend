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
                'routines.created_at as published_at',
            ]);
        $this->applySearchConditions($routineItems, $input, 'routines.account_identifier');

        $executionItems = DB::table('posts')
            ->join('routine_executions', 'posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->join('routines', 'posts.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
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
                'posts.created_at as published_at',
            ]);
        $this->applySearchConditions($executionItems, $input, 'routine_executions.executor_account_identifier');

        $paginator = DB::query()
            ->fromSub($routineItems->unionAll($executionItems), 'search_items')
            ->orderByDesc('published_at')
            ->orderBy('item_type')
            ->orderBy('routine_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());

        $routineIdentifiers = $paginator->getCollection()
            ->pluck('routine_identifier')
            ->map(static fn (mixed $identifier): string => (string) $identifier)
            ->unique()
            ->values()
            ->all();
        $tagsByRoutineIdentifier = $this->tagsByRoutineIdentifier($routineIdentifiers);

        $items = $paginator->getCollection()
            ->map(fn (object $item): array => [
                'itemType' => (string) $item->item_type,
                'routineIdentifier' => (string) $item->routine_identifier,
                'routineExecutionIdentifier' => $item->routine_execution_identifier === null ? null : (string) $item->routine_execution_identifier,
                'routineName' => (string) $item->routine_name,
                'accountIdentifier' => (string) $item->account_identifier,
                'accountName' => (string) $item->account_name,
                'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $item->account_identifier)),
                'tags' => $tagsByRoutineIdentifier[(string) $item->routine_identifier] ?? [],
                'publishedAt' => (new DateTimeImmutable((string) $item->published_at))->format(DATE_ATOM),
            ])
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
            $query->whereNotExists($this->blockExists($input->accountIdentifier(), $accountIdentifierColumn));
        }
    }

    private function blockExists(string $accountIdentifier, string $accountIdentifierColumn): \Closure
    {
        return static function ($query) use ($accountIdentifier, $accountIdentifierColumn): void {
            $query->selectRaw('1')
                ->from('blocks')
                ->where(static function ($query) use ($accountIdentifier, $accountIdentifierColumn): void {
                    $query->where('blocks.blocking_account_identifier', $accountIdentifier)
                        ->whereColumn('blocks.blocked_account_identifier', $accountIdentifierColumn);
                })
                ->orWhere(static function ($query) use ($accountIdentifier, $accountIdentifierColumn): void {
                    $query->where('blocks.blocked_account_identifier', $accountIdentifier)
                        ->whereColumn('blocks.blocking_account_identifier', $accountIdentifierColumn);
                });
        };
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
