<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\IsRoutineVisible;

use Src\Routine\Application\Usecase\Query\IsRoutineVisible\IsRoutineVisibleInterface;
use Src\Routine\Infrastructure\Query\VisibleRoutineQuery;

final class IsRoutineVisible implements IsRoutineVisibleInterface
{
    public function execute(string $routineIdentifier, string $viewerAccountIdentifier): bool
    {
        return VisibleRoutineQuery::for($routineIdentifier, $viewerAccountIdentifier)->exists();
    }
}
