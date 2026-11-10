<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRecentlyExecutedRoutines;

interface GetRecentlyExecutedRoutinesInputPort
{
    public function accountIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
