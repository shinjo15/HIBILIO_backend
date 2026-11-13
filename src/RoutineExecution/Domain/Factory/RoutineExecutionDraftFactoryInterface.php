<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Factory;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineExecutionDraftFactoryInterface
{
    public function create(
        AccountIdentifier $executorAccountIdentifier,
        RoutineIdentifier $routineIdentifier,
    ): RoutineExecutionDraft;
}
