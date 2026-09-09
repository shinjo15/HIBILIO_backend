<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountRoutinePosts;

interface GetAccountRoutinePostsInputPort
{
    public function accountIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
