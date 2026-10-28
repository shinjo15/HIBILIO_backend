<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFavoriteTagPosts;

interface GetFavoriteTagPostsOutputPort
{
    /** @return list<array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}> */
    public function posts(): array;

    public function total(): int;
}
