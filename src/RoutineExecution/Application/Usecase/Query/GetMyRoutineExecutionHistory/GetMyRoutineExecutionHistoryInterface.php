<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory;

interface GetMyRoutineExecutionHistoryInterface
{
    public function execute(GetMyRoutineExecutionHistoryInputPort $input): GetMyRoutineExecutionHistoryOutputPort;
}
