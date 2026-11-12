<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface UpdateRoutineExecutionDraftMemoInputPort
{
    public function draftCreatorAccountIdentifier(): AccountIdentifier;

    public function routineIdentifier(): RoutineIdentifier;

    public function routineExecutionMemo(): ?RoutineExecutionMemo;
}
