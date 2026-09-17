<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails;

interface GetRoutineExecutionDetailsOutputPort
{
    /** @return array{routineExecutionIdentifier: string, routineIdentifier: string, routineName: string, routineMemo: ?string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, routineExecutionMemo: ?string, executedAt: string, postedAt: string, supportCount: int, tags: list<array{tagIdentifier: string, tagName: string}>, routineExecutionActions: list<array{routineActionIdentifier: string, actionName: string, actionMemo: ?string, actionMinutes: ?int}>}|null */
    public function routineExecutionDetails(): ?array;
}
