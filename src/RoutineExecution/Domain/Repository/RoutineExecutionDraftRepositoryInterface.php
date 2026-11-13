<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\RoutineExecution;

interface RoutineExecutionDraftRepositoryInterface
{
    public function save(RoutineExecution $routineExecution): void;
}
