<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

final readonly class GetRoutineDetailsInput implements GetRoutineDetailsInputPort
{
    public function __construct(
        private string $routineIdentifier,
        private ?string $viewerAccountIdentifier = null,
    ) {}

    public function routineIdentifier(): string
    {
        return $this->routineIdentifier;
    }

    public function viewerAccountIdentifier(): ?string
    {
        return $this->viewerAccountIdentifier;
    }
}
