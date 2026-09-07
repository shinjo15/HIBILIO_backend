<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizedRoutines;

interface GetCustomizedRoutinesInterface
{
    public function execute(GetCustomizedRoutinesInputPort $input): GetCustomizedRoutinesOutputPort;
}
