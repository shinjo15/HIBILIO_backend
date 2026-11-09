<?php

declare(strict_types=1);

namespace Src\Like\Domain\Repository;

use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface LikeNotificationRepositoryInterface
{
    public function findTarget(AccountIdentifier $actor, RoutineIdentifier $routine): ?LikeNotificationTarget;

    public function save(LikeNotification $notification): void;
}
