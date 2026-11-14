<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface DeleteRoutineExecutionDraftInputPort
{
    public function executorAccountIdentifier(): AccountIdentifier;

    public function routineIdentifier(): RoutineIdentifier;
}
