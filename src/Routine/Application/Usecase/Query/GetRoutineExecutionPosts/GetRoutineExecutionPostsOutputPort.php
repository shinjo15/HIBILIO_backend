<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts;

interface GetRoutineExecutionPostsOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, executedActionCount: int, postedAt: string, routineExecutionMemo: ?string, supportCount: int}> */
    public function items(): array;
}
