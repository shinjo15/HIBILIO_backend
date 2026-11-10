<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines;

final readonly class GetMostExecutedRoutinesOutput implements GetMostExecutedRoutinesOutputPort
{
    /** @param list<array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}> $items */
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
