<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

interface GetAccountRoutineExecutionsOutputPort
{
    /** @return list<array<string, mixed>> */
    public function items(): array;

    public function total(): int;
}
