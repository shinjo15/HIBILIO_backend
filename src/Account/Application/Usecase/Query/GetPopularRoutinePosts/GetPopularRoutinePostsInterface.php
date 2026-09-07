<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetPopularRoutinePosts;

interface GetPopularRoutinePostsInterface
{
    public function execute(GetPopularRoutinePostsInputPort $input): GetPopularRoutinePostsOutputPort;
}
