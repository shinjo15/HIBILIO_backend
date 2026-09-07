<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

final readonly class GetRoutineDetailsInput implements GetRoutineDetailsInputPort
{
    public function __construct(
        private string $routineIdentifier,
    ) {}

    public function routineIdentifier(): string
    {
        return $this->routineIdentifier;
    }
}
