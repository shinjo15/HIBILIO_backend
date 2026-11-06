<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

interface GetAccountRoutineExecutionsOutputPort
{
    /** @return list<array{routineExecutionIdentifier: string, routineIdentifier: string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, executedActionCount: int, postedAt: ?string, routineExecutionMemo: ?string, supportCount: ?int, supported: ?bool}> */
    public function items(): array;

    public function total(): int;
}
