<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory;

interface GetMyRoutineExecutionHistoryOutputPort
{
    /** @return list<array{routineExecutionIdentifier: string, routineIdentifier: string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, executedActionCount: int, executedAt: string, postedAt: ?string, routineExecutionMemo: ?string, supportCount: ?int, supported: ?bool}> */
    public function items(): array;

    public function total(): int;
}
