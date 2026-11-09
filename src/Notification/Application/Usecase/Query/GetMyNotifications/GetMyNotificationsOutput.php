<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyNotifications;

final readonly class GetMyNotificationsOutput implements GetMyNotificationsOutputPort
{
    /**
     * @param  list<array{notificationIdentifier: string, status: string, content: string, createdAt: string}>  $notifications
     */
    public function __construct(private array $notifications, private int $total) {}

    public function notifications(): array
    {
        return $this->notifications;
    }

    public function total(): int
    {
        return $this->total;
    }
}
