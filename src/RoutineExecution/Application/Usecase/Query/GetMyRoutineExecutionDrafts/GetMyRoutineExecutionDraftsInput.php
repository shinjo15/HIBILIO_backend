<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts;

final readonly class GetMyRoutineExecutionDraftsInput implements GetMyRoutineExecutionDraftsInputPort
{
    public function __construct(private string $accountIdentifier) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }
}
