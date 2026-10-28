<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingPosts;

final readonly class GetFollowingPostsOutput implements GetFollowingPostsOutputPort
{
    /** @param list<array{postCategory: 'routine', customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}|array{postCategory: 'action', customizationOrigin: null, ...}> $posts */
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
