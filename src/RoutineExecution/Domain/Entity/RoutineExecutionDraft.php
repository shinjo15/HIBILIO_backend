<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Entity;

use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class RoutineExecutionDraft
{
    /** @param list<RoutineAction> $actions */
    public function __construct(
        public AccountIdentifier $owner,
        public Routine $routine,
        public array $actions,
    ) {}
}
