<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CompleteRoutineExecutionDraft;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface CompleteRoutineExecutionDraftInputPort
{
    public function executorAccountIdentifier(): AccountIdentifier;

    public function routineIdentifier(): RoutineIdentifier;
}
