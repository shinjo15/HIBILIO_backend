<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizedRoutines;

final readonly class GetCustomizedRoutinesOutput implements GetCustomizedRoutinesOutputPort
{
    /** @param list<array{routineIdentifier: string, accountIdentifier: string, accountName: string, routineName: string, routineMemo: ?string, routineExecutionMinutes: ?int, executionCount: int, customizationCount: int, likeCount: int}> $items */
    public function __construct(
        private array $items,
        private int $total,
        private bool $parentRoutineExists,
    ) {}

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function parentRoutineExists(): bool
    {
        return $this->parentRoutineExists;
    }
}
