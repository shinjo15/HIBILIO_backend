<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFavoriteTagPosts;

final readonly class GetFavoriteTagPostsOutput implements GetFavoriteTagPostsOutputPort
{
    /** @param list<array<string, mixed>> $posts */
    public function __construct(
        private array $posts,
        private int $total,
    ) {}

    public function posts(): array
    {
        return $this->posts;
    }

    public function total(): int
    {
        return $this->total;
    }
}
