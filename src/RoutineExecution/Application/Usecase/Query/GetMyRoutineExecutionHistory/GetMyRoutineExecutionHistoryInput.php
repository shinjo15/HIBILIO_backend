<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory;

final readonly class GetMyRoutineExecutionHistoryInput implements GetMyRoutineExecutionHistoryInputPort
{
    public function __construct(private string $accountIdentifier, private string $start, private string $end) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function start(): string
    {
        return $this->start;
    }

    public function end(): string
    {
        return $this->end;
    }
}
