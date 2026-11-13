<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CompleteRoutineExecutionDraft;

interface CompleteRoutineExecutionDraftInterface
{
    public function execute(CompleteRoutineExecutionDraftInputPort $input): void;
}
