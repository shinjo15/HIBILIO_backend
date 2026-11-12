<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface CreateRoutineExecutionDraftInputPort
{
    public function routineIdentifier(): RoutineIdentifier;

    public function ownerAccountIdentifier(): AccountIdentifier;
}
