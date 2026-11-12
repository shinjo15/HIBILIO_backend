<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

interface CreateRoutineExecutionDraftOutputPort
{
    public function created(): bool;
}
