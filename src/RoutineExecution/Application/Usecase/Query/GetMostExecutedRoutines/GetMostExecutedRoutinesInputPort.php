<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines;

interface GetMostExecutedRoutinesInputPort
{
    public function accountIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
