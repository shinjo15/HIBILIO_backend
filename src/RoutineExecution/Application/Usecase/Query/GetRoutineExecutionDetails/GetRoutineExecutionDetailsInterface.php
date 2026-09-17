<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails;

interface GetRoutineExecutionDetailsInterface
{
    public function execute(GetRoutineExecutionDetailsInputPort $input): GetRoutineExecutionDetailsOutputPort;
}
