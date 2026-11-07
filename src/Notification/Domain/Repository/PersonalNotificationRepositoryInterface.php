<?php

declare(strict_types=1);

namespace Src\Notification\Domain\Repository;

use Src\Notification\Domain\Entity\PersonalNotification;
use Src\Notification\Domain\ValueObject\NotificationIdentifier;

interface PersonalNotificationRepositoryInterface
{
    public function find(NotificationIdentifier $notificationIdentifier): ?PersonalNotification;

    public function save(PersonalNotification $notification): void;
}
