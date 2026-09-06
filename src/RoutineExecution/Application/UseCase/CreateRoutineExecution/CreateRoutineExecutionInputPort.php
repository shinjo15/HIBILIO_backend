<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecution;

use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface CreateRoutineExecutionInputPort
{
    public function executorAccountIdentifier(): AccountIdentifier;

    public function routineIdentifier(): RoutineIdentifier;

    /** @return list<RoutineActionIdentifier> */
    public function executedRoutineActionIdentifiers(): array;

    public function routineExecutionMemo(): ?RoutineExecutionMemo;
}
