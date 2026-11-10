<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines;

interface GetRecentExecutedRoutinesInterface
{
    public function execute(GetRecentExecutedRoutinesInputPort $input): GetRecentExecutedRoutinesOutputPort;
}
