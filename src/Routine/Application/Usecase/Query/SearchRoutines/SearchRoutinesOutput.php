<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

final readonly class SearchRoutinesOutput implements SearchRoutinesOutputPort
{
    /** @param list<array{itemType: string, routineIdentifier: string, routineExecutionIdentifier: ?string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, tags: list<array{tagIdentifier: string, tagName: string}>, publishedAt: string}> $items */
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
