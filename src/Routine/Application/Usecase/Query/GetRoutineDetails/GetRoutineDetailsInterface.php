<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

interface GetRoutineDetailsInterface
{
    public function execute(GetRoutineDetailsInputPort $input): GetRoutineDetailsOutputPort;
}
