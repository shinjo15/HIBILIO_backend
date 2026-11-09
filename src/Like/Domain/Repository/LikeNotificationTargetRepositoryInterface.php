<?php

declare(strict_types=1);

namespace Src\Like\Domain\Repository;

use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

interface LikeNotificationTargetRepositoryInterface
{
    public function find(AccountIdentifier $actor, PostIdentifier $post): ?LikeNotificationTarget;
}
