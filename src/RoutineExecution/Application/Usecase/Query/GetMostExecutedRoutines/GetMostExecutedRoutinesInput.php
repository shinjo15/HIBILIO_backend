<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines;

final readonly class GetMostExecutedRoutinesInput implements GetMostExecutedRoutinesInputPort
{
    public function __construct(private string $accountIdentifier, private int $page, private int $numberOfItemsPerPage) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
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
