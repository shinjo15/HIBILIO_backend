<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizedRoutines;

interface GetCustomizedRoutinesOutputPort
{
    /** @return list<array{routineIdentifier: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, routineName: string, routineMemo: ?string, routineExecutionMinutes: ?int, executionCount: int, customizationCount: int, likeCount: int, customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null}> */
    public function items(): array;

    public function total(): int;

    public function parentRoutineExists(): bool;
}
