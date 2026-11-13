<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions;

interface UpdateRoutineExecutionDraftActionsInterface
{
    public function execute(UpdateRoutineExecutionDraftActionsInputPort $input): void;
}
