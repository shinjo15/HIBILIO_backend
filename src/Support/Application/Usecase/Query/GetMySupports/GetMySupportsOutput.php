<?php

declare(strict_types=1);

namespace Src\Support\Application\Usecase\Query\GetMySupports;

final readonly class GetMySupportsOutput implements GetMySupportsOutputPort
{
    /**
     * @param  list<array{postIdentifier: string, routineIdentifier: string, routineExecutionIdentifier: ?string, postCategory: string, postLikeCount: int, postSupportCount: int, supportedAt: string}>  $items
     */
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
