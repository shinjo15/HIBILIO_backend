<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;

interface RoutineExecutionActionDraftRepositoryInterface
{
    public function save(RoutineExecutionAction $action, RoutineExecutionDraft $draft): void;

    public function delete(RoutineExecutionAction $action, RoutineExecutionDraft $draft): void;
}
