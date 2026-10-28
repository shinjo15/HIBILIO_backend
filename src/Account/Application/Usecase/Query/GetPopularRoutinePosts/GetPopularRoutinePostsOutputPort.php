<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetPopularRoutinePosts;

interface GetPopularRoutinePostsOutputPort
{
    /** @return list<array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}> */
    public function posts(): array;

    public function total(): int;
}
