<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

interface CreateRoutineExecutionDraftInterface
{
    public function execute(CreateRoutineExecutionDraftInputPort $input): void;
}
