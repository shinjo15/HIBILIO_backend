<?php

declare(strict_types=1);

namespace Src\Post\Infrastructure\Factory;

use Src\Post\Domain\Entity\Post;
use Src\Post\Domain\Factory\PostFactoryInterface;
use Src\Post\Domain\ValueObject\PostCategory;
use Src\Post\Domain\ValueObject\PostLikeCount;
use Src\Post\Domain\ValueObject\PostSupportCount;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class PostFactory implements PostFactoryInterface
{
    public function __construct(
        private UuidServiceInterface $uuidService,
    ) {}

    public function createRoutinePost(RoutineIdentifier $routineIdentifier): Post
    {
        return Post::create(
            new PostIdentifier($this->uuidService->generate()),
            $routineIdentifier,
            null,
            PostCategory::ROUTINE,
            new PostLikeCount(0),
            new PostSupportCount(0),
        );
    }

    public function createActionPost(
        RoutineIdentifier $routineIdentifier,
        RoutineExecutionIdentifier $routineExecutionIdentifier,
    ): Post {
        return Post::create(
            new PostIdentifier($this->uuidService->generate()),
            $routineIdentifier,
            $routineExecutionIdentifier,
            PostCategory::ACTION,
            new PostLikeCount(0),
            new PostSupportCount(0),
        );
    }
}
