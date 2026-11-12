<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class UpdateRoutineExecutionDraftMemoInput implements UpdateRoutineExecutionDraftMemoInputPort
{
    public function __construct(private AccountIdentifier $creator, private RoutineIdentifier $routine, private ?RoutineExecutionMemo $memo) {}

    public function draftCreatorAccountIdentifier(): AccountIdentifier
    {
        return $this->creator;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routine;
    }

    public function routineExecutionMemo(): ?RoutineExecutionMemo
    {
        return $this->memo;
    }
}
