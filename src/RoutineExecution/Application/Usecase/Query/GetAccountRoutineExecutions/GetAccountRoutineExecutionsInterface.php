<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

interface GetAccountRoutineExecutionsInterface
{
    public function execute(GetAccountRoutineExecutionsInputPort $input): GetAccountRoutineExecutionsOutputPort;
}
