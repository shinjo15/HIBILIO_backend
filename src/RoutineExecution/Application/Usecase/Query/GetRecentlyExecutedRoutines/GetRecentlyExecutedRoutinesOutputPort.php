<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRecentlyExecutedRoutines;

interface GetRecentlyExecutedRoutinesOutputPort
{
    /** @return list<array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}> */
    public function items(): array;

    public function total(): int;
}
