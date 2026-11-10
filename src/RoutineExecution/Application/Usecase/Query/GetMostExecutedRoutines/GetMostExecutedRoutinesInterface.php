<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines;

interface GetMostExecutedRoutinesInterface
{
    public function execute(GetMostExecutedRoutinesInputPort $input): GetMostExecutedRoutinesOutputPort;
}
