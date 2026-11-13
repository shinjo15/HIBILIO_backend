<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface UpdateRoutineExecutionDraftActionsInputPort
{
    public function executorAccountIdentifier(): AccountIdentifier;

    public function routineIdentifier(): RoutineIdentifier;

    public function routineActionIdentifier(): RoutineActionIdentifier;

    public function isExecuted(): bool;
}
