<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Repository;

use Src\Routine\Domain\Entity\RoutineAction;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;

interface RoutineActionRepositoryInterface
{
    /** @param list<RoutineActionIdentifier> $routineActionIdentifiers
     * @return list<RoutineAction>
     */
    public function findByIdentifiers(array $routineActionIdentifiers): array;

    public function save(RoutineAction $routineAction): void;
}
