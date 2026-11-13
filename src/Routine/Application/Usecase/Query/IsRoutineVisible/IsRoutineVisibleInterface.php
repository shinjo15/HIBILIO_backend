<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\IsRoutineVisible;

interface IsRoutineVisibleInterface
{
    public function execute(string $routineIdentifier, string $viewerAccountIdentifier): bool;
}
