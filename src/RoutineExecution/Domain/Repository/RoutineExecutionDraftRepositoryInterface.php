<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Repository;

use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineExecutionDraftRepositoryInterface
{
    public function find(AccountIdentifier $executorAccountIdentifier, RoutineIdentifier $routineIdentifier): ?RoutineExecutionDraft;

    public function save(RoutineExecutionDraft $routineExecutionDraft): void;

    public function updateMemo(RoutineExecutionDraft $routineExecutionDraft): void;

    /** 指定した行動のみをEntityの現在の状態に更新する。 */
    public function updateAction(RoutineExecutionDraft $routineExecutionDraft, RoutineActionIdentifier $actionIdentifier): void;
}
