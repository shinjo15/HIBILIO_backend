<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class UpdateRoutineExecutionDraftMemoInput implements UpdateRoutineExecutionDraftMemoInputPort
{
    public function __construct(private AccountIdentifier $executorAccountIdentifier, private RoutineIdentifier $routineIdentifier, private ?RoutineExecutionMemo $memo) {}

    public function executorAccountIdentifier(): AccountIdentifier
    {
        return $this->executorAccountIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function memo(): ?RoutineExecutionMemo
    {
        return $this->memo;
    }
}
