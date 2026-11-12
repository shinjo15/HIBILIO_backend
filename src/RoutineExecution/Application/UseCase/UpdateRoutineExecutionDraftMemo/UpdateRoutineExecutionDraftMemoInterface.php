<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

interface UpdateRoutineExecutionDraftMemoInterface
{
    public function execute(UpdateRoutineExecutionDraftMemoInputPort $input): void;
}
