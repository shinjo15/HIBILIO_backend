<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;

interface RoutineExecutionDraftRepositoryInterface
{
    public function save(RoutineExecutionDraft $routineExecutionDraft): void;
}
