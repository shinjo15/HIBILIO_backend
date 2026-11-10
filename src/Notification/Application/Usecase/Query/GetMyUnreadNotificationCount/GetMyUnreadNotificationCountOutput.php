<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount;

final readonly class GetMyUnreadNotificationCountOutput implements GetMyUnreadNotificationCountOutputPort
{
    public function __construct(private int $unreadCount) {}

    public function unreadCount(): int
    {
        return $this->unreadCount;
    }
}
