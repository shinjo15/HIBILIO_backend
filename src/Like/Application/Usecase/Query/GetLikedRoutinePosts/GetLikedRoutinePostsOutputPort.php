<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikedRoutinePosts;

interface GetLikedRoutinePostsOutputPort
{
    /** @return list<array{postIdentifier: string, routineIdentifier: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, postedAt: string, routineName: string, routineExecutionMinutes: ?int, tags: list<array{tagIdentifier: string, tagName: string}>, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, postLikeCount: int, postSupportCount: int, liked: bool, executionCount: int, customizationCount: int, likedAt: string, customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null}> */
    public function items(): array;

    public function total(): int;
}
