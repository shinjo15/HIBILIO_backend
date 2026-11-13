<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts;

interface GetMyRoutineExecutionDraftsOutputPort
{
    public function items(): array;

    public function total(): int;
}
