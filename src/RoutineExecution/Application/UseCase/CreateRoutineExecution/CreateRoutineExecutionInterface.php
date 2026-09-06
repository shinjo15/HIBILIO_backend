<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecution;

interface CreateRoutineExecutionInterface
{
    public function execute(CreateRoutineExecutionInputPort $input): void;
}
