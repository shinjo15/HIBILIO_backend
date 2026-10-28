<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

interface SearchRoutinesOutputPort
{
    /** @return list<array{itemType: 'routine', routineIdentifier: string, routineExecutionIdentifier: null, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, tags: list<array{tagIdentifier: string, tagName: string}>, publishedAt: string, postedAt: ?string, postIdentifier: ?string, routineExecutionMinutes: ?int, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, executionCount: int, customizationCount: int, postLikeCount: ?int, liked: ?bool, customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null}|array{itemType: 'routine_execution', routineIdentifier: string, routineExecutionIdentifier: string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, tags: list<array{tagIdentifier: string, tagName: string}>, publishedAt: string, postedAt: string, postIdentifier: string, routineExecutionMinutes: ?int, routineActions: list<array{routineActionIdentifier: string, actionName: string, actionMinutes: ?int}>, executionCount: int, customizationCount: int, postLikeCount: int, supportCount: int, supported: bool, executedActionCount: int, routineExecutionMemo: ?string, customizationOrigin: null}> */
    public function items(): array;

    public function total(): int;
}
