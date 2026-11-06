<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyRoutines;

interface GetMyRoutinesInterface
{
    public function execute(GetMyRoutinesInputPort $input): GetMyRoutinesOutputPort;
}
