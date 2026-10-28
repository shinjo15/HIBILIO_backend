<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

final readonly class SearchRoutinesOutput implements SearchRoutinesOutputPort
{
    /** @param list<array{itemType: 'routine', routineIdentifier: string, routineExecutionIdentifier: null, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, tags: list<array{tagIdentifier: string, tagName: string}>, publishedAt: string, postedAt: ?string, postIdentifier: ?string, routineExecutionMinutes: ?int, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, executionCount: int, customizationCount: int, postLikeCount: ?int, liked: ?bool, customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null}|array{itemType: 'routine_execution', routineIdentifier: string, routineExecutionIdentifier: string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, tags: list<array{tagIdentifier: string, tagName: string}>, publishedAt: string, postedAt: string, postIdentifier: string, routineExecutionMinutes: ?int, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, executionCount: int, customizationCount: int, postLikeCount: int, supportCount: int, supported: bool, executedActionCount: int, routineExecutionMemo: ?string, customizationOrigin: null}> $items */
    public function __construct(private array $items, private int $total) {}

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }
}
