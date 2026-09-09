<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

final readonly class GetAccountRoutineExecutionsOutput implements GetAccountRoutineExecutionsOutputPort
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(private array $items, private int $total) {}

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }
}
