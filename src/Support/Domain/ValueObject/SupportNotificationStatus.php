<?php

declare(strict_types=1);

namespace Src\Support\Domain\ValueObject;

enum SupportNotificationStatus: string
{
    case Unread = 'unread';
    case Read = 'read';
}
