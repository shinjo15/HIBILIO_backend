<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountPosts;

final readonly class GetAccountPostsOutput implements GetAccountPostsOutputPort
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
