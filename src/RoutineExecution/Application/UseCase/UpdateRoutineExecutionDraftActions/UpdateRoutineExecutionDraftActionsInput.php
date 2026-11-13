<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class UpdateRoutineExecutionDraftActionsInput implements UpdateRoutineExecutionDraftActionsInputPort
{
    public function __construct(private AccountIdentifier $account, private RoutineIdentifier $routine, private RoutineActionIdentifier $action, private bool $executed) {}

    public function executorAccountIdentifier(): AccountIdentifier
    {
        return $this->account;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routine;
    }

    public function routineActionIdentifier(): RoutineActionIdentifier
    {
        return $this->action;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }
}
