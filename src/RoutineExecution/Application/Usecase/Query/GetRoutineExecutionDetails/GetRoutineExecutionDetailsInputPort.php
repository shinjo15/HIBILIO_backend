<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails;

interface GetRoutineExecutionDetailsInputPort
{
    public function routineExecutionIdentifier(): string;

    public function accountIdentifier(): ?string;
}
