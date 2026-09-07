<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts;

interface GetRoutineExecutionPostsInterface
{
    public function execute(GetRoutineExecutionPostsInputPort $input): GetRoutineExecutionPostsOutputPort;
}
