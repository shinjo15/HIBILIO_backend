<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Repository;

use Src\Post\Domain\Entity\Post;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

interface PostRepositoryInterface
{
    public function find(PostIdentifier $postIdentifier): ?Post;

    public function save(Post $post): void;
}
