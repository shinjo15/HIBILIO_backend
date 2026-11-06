<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Repository;

use Src\Routine\Domain\Entity\Routine;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface RoutineRepositoryInterface
{
    public function find(RoutineIdentifier $identifier): ?Routine;

    public function add(Routine $routine): void;

    public function saveVideoLink(Routine $routine): void;
}
