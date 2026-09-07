<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetPopularRoutinePosts;

interface GetPopularRoutinePostsInputPort
{
    public function accountIdentifier(): string;

    public function numberOfItemsPerPage(): int;

    public function page(): int;
}
