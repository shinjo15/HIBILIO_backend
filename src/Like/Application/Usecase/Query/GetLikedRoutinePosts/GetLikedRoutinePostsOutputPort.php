<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikedRoutinePosts;

interface GetLikedRoutinePostsOutputPort
{
    /** @return list<array{postIdentifier: string, routineIdentifier: string, accountIdentifier: string, accountName: string, postedAt: string, routineName: string, routineExecutionMinutes: ?int, tags: list<array{tagIdentifier: string, tagName: string}>, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, postLikeCount: int, postSupportCount: int, executionCount: int, customizationCount: int, likedAt: string}> */
    public function items(): array;

    public function total(): int;
}
