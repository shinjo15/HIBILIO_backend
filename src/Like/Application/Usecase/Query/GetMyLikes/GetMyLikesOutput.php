<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetMyLikes;

final readonly class GetMyLikesOutput implements GetMyLikesOutputPort
{
    /**
     * @param  list<array{postIdentifier: string, routineIdentifier: string, postCategory: string, postLikeCount: int, postSupportCount: int, likedAt: string}>  $items
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
