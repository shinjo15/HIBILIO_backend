<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Repository;

use Src\Routine\Domain\Entity\Routine;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineRepositoryInterface
{
    public function findByIdentifier(RoutineIdentifier $identifier): ?Routine;

    public function save(Routine $routine): void;
}
