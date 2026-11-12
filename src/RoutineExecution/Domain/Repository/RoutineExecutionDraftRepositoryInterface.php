<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineExecutionDraftRepositoryInterface
{
    /** @param list<RoutineAction> $actions */
    public function save(AccountIdentifier $draftCreatorAccountIdentifier, Routine $routine, array $actions): void;

    public function updateMemo(AccountIdentifier $creator, RoutineIdentifier $routine, ?RoutineExecutionMemo $memo): bool;
}
