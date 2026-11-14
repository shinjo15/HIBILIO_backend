<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft;

interface DeleteRoutineExecutionDraftInterface
{
    public function execute(DeleteRoutineExecutionDraftInputPort $input): void;
}
