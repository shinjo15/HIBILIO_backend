<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountRoutinePosts;

interface GetAccountRoutinePostsOutputPort
{
    /** @return list<array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}> */
    public function items(): array;

    public function total(): int;
}
