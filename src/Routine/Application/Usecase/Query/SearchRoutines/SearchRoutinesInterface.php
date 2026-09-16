<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

interface SearchRoutinesInterface
{
    public function execute(SearchRoutinesInputPort $input): SearchRoutinesOutputPort;
}
