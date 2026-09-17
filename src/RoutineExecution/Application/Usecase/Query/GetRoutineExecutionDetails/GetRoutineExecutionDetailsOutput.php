<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails;

final readonly class GetRoutineExecutionDetailsOutput implements GetRoutineExecutionDetailsOutputPort
{
    /** @param array{routineExecutionIdentifier: string, routineIdentifier: string, routineName: string, routineMemo: ?string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, routineExecutionMemo: ?string, executedAt: string, postedAt: string, supportCount: int, tags: list<array{tagIdentifier: string, tagName: string}>, routineExecutionActions: list<array{routineActionIdentifier: string, actionName: string, actionMemo: ?string, actionMinutes: ?int}>}|null $routineExecutionDetails */
    public function __construct(private ?array $routineExecutionDetails) {}

    public function routineExecutionDetails(): ?array
    {
        return $this->routineExecutionDetails;
    }
}
