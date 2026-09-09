<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikedRoutinePosts;

interface GetLikedRoutinePostsInputPort
{
    public function accountIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
