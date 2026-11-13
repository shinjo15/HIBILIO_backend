<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts;

interface GetMyRoutineExecutionDraftsInterface
{
    public function execute(GetMyRoutineExecutionDraftsInputPort $input): GetMyRoutineExecutionDraftsOutputPort;
}
