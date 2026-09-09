<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikedRoutinePosts;

interface GetLikedRoutinePostsInterface
{
    public function execute(GetLikedRoutinePostsInputPort $input): GetLikedRoutinePostsOutputPort;
}
