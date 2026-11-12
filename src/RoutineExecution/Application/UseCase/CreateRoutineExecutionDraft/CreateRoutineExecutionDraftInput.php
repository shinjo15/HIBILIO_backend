<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class CreateRoutineExecutionDraftInput implements CreateRoutineExecutionDraftInputPort
{
    public function __construct(private RoutineIdentifier $routineIdentifier, private AccountIdentifier $draftCreatorAccountIdentifier) {}

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function draftCreatorAccountIdentifier(): AccountIdentifier
    {
        return $this->draftCreatorAccountIdentifier;
    }
}
