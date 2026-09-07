<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts;

interface GetRoutineExecutionPostsInputPort
{
    public function routineIdentifier(): string;
}
