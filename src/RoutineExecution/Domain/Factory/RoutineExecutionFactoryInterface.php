<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Factory;

use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineExecutionFactoryInterface
{
    public function create(
        AccountIdentifier $executorAccountIdentifier,
        RoutineIdentifier $routineIdentifier,
        ?RoutineExecutionMemo $routineExecutionMemo,
    ): RoutineExecution;
}
