<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizedRoutines;

interface GetCustomizedRoutinesOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, routineName: string, routineMemo: ?string, routineExecutionMinutes: ?int, executionCount: int, customizationCount: int, likeCount: int}> */
    public function items(): array;

    public function total(): int;

    public function parentRoutineExists(): bool;
}
