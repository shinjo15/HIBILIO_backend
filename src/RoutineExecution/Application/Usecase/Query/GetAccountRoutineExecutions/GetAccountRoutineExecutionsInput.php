<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

final readonly class GetAccountRoutineExecutionsInput implements GetAccountRoutineExecutionsInputPort
{
    public function __construct(private string $accountIdentifier, private int $page, private int $numberOfItemsPerPage, private ?string $viewerAccountIdentifier = null, private ?string $start = null, private ?string $end = null) {}

    public function start(): ?string
    {
        return $this->start;
    }

    public function end(): ?string
    {
        return $this->end;
    }

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function viewerAccountIdentifier(): ?string
    {
        return $this->viewerAccountIdentifier;
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
