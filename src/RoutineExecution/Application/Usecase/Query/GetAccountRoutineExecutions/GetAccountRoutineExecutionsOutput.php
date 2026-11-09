<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

final readonly class GetAccountRoutineExecutionsOutput implements GetAccountRoutineExecutionsOutputPort
{
    /** @param list<array{routineExecutionIdentifier: string, routineIdentifier: string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, executedActionCount: int, executedAt: string, postedAt: ?string, routineExecutionMemo: ?string, supportCount: ?int, supported: ?bool}> $items */
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
