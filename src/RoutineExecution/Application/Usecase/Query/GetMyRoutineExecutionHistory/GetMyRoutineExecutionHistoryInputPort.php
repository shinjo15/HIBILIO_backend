<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory;

interface GetMyRoutineExecutionHistoryInputPort
{
    public function accountIdentifier(): string;

    public function start(): string;

    public function end(): string;
}
