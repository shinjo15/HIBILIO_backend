<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts;

interface GetMyRoutineExecutionDraftsInputPort
{
    public function accountIdentifier(): string;
}
