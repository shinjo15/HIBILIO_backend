<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Entity;

use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraft
{
    public function __construct(
        private readonly RoutineExecutionIdentifier $routineExecutionIdentifier,
        private readonly AccountIdentifier $executorAccountIdentifier,
        private readonly RoutineIdentifier $routineIdentifier,
        private readonly ExecutedAt $executedAt,
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

    public function changeMemo(?RoutineExecutionMemo $memo): void
    {
        $this->routineExecutionMemo = $memo;
    }
}
