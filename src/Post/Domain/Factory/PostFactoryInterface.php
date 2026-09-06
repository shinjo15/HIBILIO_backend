<?php

declare(strict_types=1);

namespace Src\Post\Domain\Factory;

use Src\Post\Domain\Entity\Post;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface PostFactoryInterface
{
    public function createRoutinePost(RoutineIdentifier $routineIdentifier): Post;

    public function createActionPost(
        RoutineIdentifier $routineIdentifier,
        RoutineExecutionIdentifier $routineExecutionIdentifier,
    ): Post;
}
