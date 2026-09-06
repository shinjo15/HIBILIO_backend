<?php

declare(strict_types=1);

namespace Src\Post\Domain\Entity;

use InvalidArgumentException;
use Src\Post\Domain\Exception\UnsupportedPostLikeException;
use Src\Post\Domain\Exception\UnsupportedPostSupportException;
use Src\Post\Domain\ValueObject\PostCategory;
use Src\Post\Domain\ValueObject\PostLikeCount;
use Src\Post\Domain\ValueObject\PostSupportCount;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class Post
{
    private function __construct(
        private readonly PostIdentifier $postIdentifier,
        private readonly RoutineIdentifier $routineIdentifier,
        private readonly ?RoutineExecutionIdentifier $routineExecutionIdentifier,
        private readonly PostCategory $postCategory,
        private readonly PostLikeCount $postLikeCount,
        private readonly PostSupportCount $postSupportCount,
    ) {}

    public static function create(
        PostIdentifier $postIdentifier,
        RoutineIdentifier $routineIdentifier,
        ?RoutineExecutionIdentifier $routineExecutionIdentifier,
        PostCategory $postCategory,
        PostLikeCount $postLikeCount,
        PostSupportCount $postSupportCount,
    ): self {
        if ($postCategory === PostCategory::ACTION && $routineExecutionIdentifier === null) {
            throw new InvalidArgumentException('実行投稿にはルーティン実行が必要です。');
        }

        if ($postCategory === PostCategory::ROUTINE && $routineExecutionIdentifier !== null) {
            throw new InvalidArgumentException('ルーティン投稿にはルーティン実行を指定できません。');
        }

        return new self(
            postIdentifier: $postIdentifier,
            routineIdentifier: $routineIdentifier,
            routineExecutionIdentifier: $routineExecutionIdentifier,
            postCategory: $postCategory,
            postLikeCount: $postLikeCount,
            postSupportCount: $postSupportCount,
        );
    }

    public function postIdentifier(): PostIdentifier
    {
        return $this->postIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function routineExecutionIdentifier(): ?RoutineExecutionIdentifier
    {
        return $this->routineExecutionIdentifier;
    }

    public function postCategory(): PostCategory
    {
        return $this->postCategory;
    }

    public function postLikeCount(): PostLikeCount
    {
        return $this->postLikeCount;
    }

    public function postSupportCount(): PostSupportCount
    {
        return $this->postSupportCount;
    }

    public function incrementLikeCount(): self
    {
        if ($this->postCategory !== PostCategory::ROUTINE) {
            throw new UnsupportedPostLikeException;
        }

        return new self(
            $this->postIdentifier,
            $this->routineIdentifier,
            $this->routineExecutionIdentifier,
            $this->postCategory,
            new PostLikeCount($this->postLikeCount->value() + 1),
            $this->postSupportCount,
        );
    }

    public function incrementSupportCount(): self
    {
        if ($this->postCategory !== PostCategory::ACTION) {
            throw new UnsupportedPostSupportException;
        }

        return new self(
            $this->postIdentifier,
            $this->routineIdentifier,
            $this->routineExecutionIdentifier,
            $this->postCategory,
            $this->postLikeCount,
            new PostSupportCount($this->postSupportCount->value() + 1),
        );
    }
}
