<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizedRoutines;

interface GetCustomizedRoutinesInputPort
{
    public function parentRoutineIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
