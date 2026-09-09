<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikedRoutinePosts;

final readonly class GetLikedRoutinePostsOutput implements GetLikedRoutinePostsOutputPort
{
    /** @param list<array{postIdentifier: string, routineIdentifier: string, accountIdentifier: string, accountName: string, postedAt: string, routineName: string, routineExecutionMinutes: ?int, tags: list<array{tagIdentifier: string, tagName: string}>, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, postLikeCount: int, postSupportCount: int, executionCount: int, customizationCount: int, likedAt: string}> $items */
    public function __construct(
        private array $items,
        private int $total,
    ) {}

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }
}
