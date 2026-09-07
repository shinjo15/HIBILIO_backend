<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

interface GetRoutineDetailsOutputPort
{
    /** @return array<string, mixed>|null */
    public function routineDetails(): ?array;
}
