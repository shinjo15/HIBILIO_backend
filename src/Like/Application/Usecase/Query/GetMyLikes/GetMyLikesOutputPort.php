<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetMyLikes;

interface GetMyLikesOutputPort
{
    /**
     * @return list<array{postIdentifier: string, routineIdentifier: string, postCategory: string, postLikeCount: int, postSupportCount: int, likedAt: string}>
     */
    public function items(): array;

    public function total(): int;
}
