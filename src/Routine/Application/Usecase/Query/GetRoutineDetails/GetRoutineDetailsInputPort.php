<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

interface GetRoutineDetailsInputPort
{
    public function routineIdentifier(): string;
}
