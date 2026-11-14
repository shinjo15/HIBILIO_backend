<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class DeleteRoutineExecutionDraftInput implements DeleteRoutineExecutionDraftInputPort
{
    public function __construct(private AccountIdentifier $executorAccountIdentifier, private RoutineIdentifier $routineIdentifier) {}

    public function executorAccountIdentifier(): AccountIdentifier
    {
        return $this->executorAccountIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }
}
