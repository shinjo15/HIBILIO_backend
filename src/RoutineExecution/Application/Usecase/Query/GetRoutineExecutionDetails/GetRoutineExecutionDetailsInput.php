<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails;

final readonly class GetRoutineExecutionDetailsInput implements GetRoutineExecutionDetailsInputPort
{
    public function __construct(private string $routineExecutionIdentifier, private ?string $accountIdentifier) {}

    public function routineExecutionIdentifier(): string
    {
        return $this->routineExecutionIdentifier;
    }

    public function accountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }
}
