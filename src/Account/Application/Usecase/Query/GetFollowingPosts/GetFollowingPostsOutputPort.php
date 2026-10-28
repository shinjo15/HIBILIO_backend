<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingPosts;

interface GetFollowingPostsOutputPort
{
    /** @return list<array{postCategory: 'routine', customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}|array{postCategory: 'action', customizationOrigin: null, ...}> */
    public function posts(): array;

    public function total(): int;
}
