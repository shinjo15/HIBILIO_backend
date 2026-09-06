<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Entity;

use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class RoutineExecution
{
    public function __construct(
        private RoutineExecutionIdentifier $routineExecutionIdentifier,
        private AccountIdentifier $executorAccountIdentifier,
        private RoutineIdentifier $routineIdentifier,
        private ExecutedAt $executedAt,
        private ?RoutineExecutionMemo $routineExecutionMemo,
    ) {}

    public function routineExecutionIdentifier(): RoutineExecutionIdentifier
    {
        return $this->routineExecutionIdentifier;
    }

    public function executorAccountIdentifier(): AccountIdentifier
    {
        return $this->executorAccountIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function executedAt(): ExecutedAt
    {
        return $this->executedAt;
    }

    public function routineExecutionMemo(): ?RoutineExecutionMemo
    {
        return $this->routineExecutionMemo;
    }
}
