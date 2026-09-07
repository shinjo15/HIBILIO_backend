<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFavoriteTagPosts;

interface GetFavoriteTagPostsOutputPort
{
    /** @return list<array<string, mixed>> */
    public function posts(): array;

    public function total(): int;
}
