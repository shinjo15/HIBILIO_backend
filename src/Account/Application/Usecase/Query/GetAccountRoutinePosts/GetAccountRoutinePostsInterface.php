<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountRoutinePosts;

interface GetAccountRoutinePostsInterface
{
    public function execute(GetAccountRoutinePostsInputPort $input): GetAccountRoutinePostsOutputPort;
}
