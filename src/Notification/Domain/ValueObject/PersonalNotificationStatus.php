<?php

declare(strict_types=1);

namespace Src\Notification\Domain\ValueObject;

enum PersonalNotificationStatus: string
{
    case Unread = 'unread';
    case Read = 'read';
}
