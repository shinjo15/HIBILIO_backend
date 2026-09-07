<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizedRoutines;

final readonly class GetCustomizedRoutinesInput implements GetCustomizedRoutinesInputPort
{
    public function __construct(
        private string $parentRoutineIdentifier,
        private int $page,
        private int $numberOfItemsPerPage,
    ) {}

    public function parentRoutineIdentifier(): string
    {
        return $this->parentRoutineIdentifier;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }
}
