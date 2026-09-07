<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts;

final readonly class GetRoutineExecutionPostsInput implements GetRoutineExecutionPostsInputPort
{
    public function __construct(
        private string $routineIdentifier,
    ) {}

    public function routineIdentifier(): string
    {
        return $this->routineIdentifier;
    }
}
