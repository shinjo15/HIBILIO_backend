<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetPopularRoutinePosts;

interface GetPopularRoutinePostsOutputPort
{
    /** @return list<array<string, mixed>> */
    public function posts(): array;

    public function total(): int;
}
