<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRecentlyExecutedRoutines;

interface GetRecentlyExecutedRoutinesInterface
{
    public function execute(GetRecentlyExecutedRoutinesInputPort $input): GetRecentlyExecutedRoutinesOutputPort;
}
