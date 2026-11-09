<?php

declare(strict_types=1);

namespace Src\Like\Domain\Repository;

use Src\Like\Domain\Entity\LikeNotification;

interface LikeNotificationRepositoryInterface
{
    public function save(LikeNotification $notification): void;
}
