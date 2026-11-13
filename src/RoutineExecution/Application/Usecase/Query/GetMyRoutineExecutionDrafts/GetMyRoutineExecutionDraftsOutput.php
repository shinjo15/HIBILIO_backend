<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts;

final readonly class GetMyRoutineExecutionDraftsOutput implements GetMyRoutineExecutionDraftsOutputPort
{
    public function __construct(private array $items) {}

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return count($this->items);
    }
}
