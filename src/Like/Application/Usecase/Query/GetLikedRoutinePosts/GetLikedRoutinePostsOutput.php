<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikedRoutinePosts;

final readonly class GetLikedRoutinePostsOutput implements GetLikedRoutinePostsOutputPort
{
    /** @param list<array{postIdentifier: string, routineIdentifier: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, postedAt: string, routineName: string, routineExecutionMinutes: ?int, tags: list<array{tagIdentifier: string, tagName: string}>, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, postLikeCount: int, postSupportCount: int, liked: bool, executionCount: int, customizationCount: int, likedAt: string, customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null}> $items */
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
