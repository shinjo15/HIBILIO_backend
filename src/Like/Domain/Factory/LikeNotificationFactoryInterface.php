<?php

declare(strict_types=1);

namespace Src\Like\Domain\Factory;

use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;

interface LikeNotificationFactoryInterface
{
    public function create(LikeNotificationTarget $target): LikeNotification;
}
