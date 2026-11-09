<?php

declare(strict_types=1);

namespace Src\Like\Domain\ValueObject;

enum LikeNotificationStatus: string
{
    case Unread = 'unread';
    case Read = 'read';
}
