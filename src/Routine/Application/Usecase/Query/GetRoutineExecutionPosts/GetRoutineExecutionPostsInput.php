<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts;

final readonly class GetRoutineExecutionPostsInput implements GetRoutineExecutionPostsInputPort
{
    public function __construct(
        private string $routineIdentifier,
        private int $page,
        private int $numberOfItemsPerPage,
    ) {}

    public function routineIdentifier(): string
    {
        return $this->routineIdentifier;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }
}
