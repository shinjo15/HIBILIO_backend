<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts;

final readonly class GetRoutineExecutionPostsOutput implements GetRoutineExecutionPostsOutputPort
{
    /** @param list<array{accountIdentifier: string, accountName: string, executedActionCount: int, postedAt: string, routineExecutionIdentifier: string, routineExecutionMemo: ?string, supportCount: int}> $items */
    public function __construct(
        private array $items,
        private int $total,
    ) {}

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }
}
