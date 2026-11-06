<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Repository;

use Src\Routine\Domain\Entity\Routine;

interface RoutineRepositoryInterface
{
    public function save(Routine $routine): void;
}
