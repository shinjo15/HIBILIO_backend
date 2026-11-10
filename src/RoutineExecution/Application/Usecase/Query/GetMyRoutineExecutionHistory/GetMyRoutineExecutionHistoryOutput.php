<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory;

final readonly class GetMyRoutineExecutionHistoryOutput implements GetMyRoutineExecutionHistoryOutputPort
{
    /** @param list<array{routineExecutionIdentifier: string, routineIdentifier: string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, executedActionCount: int, executedAt: string, postedAt: ?string, routineExecutionMemo: ?string, supportCount: ?int, supported: ?bool}> $items */
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
