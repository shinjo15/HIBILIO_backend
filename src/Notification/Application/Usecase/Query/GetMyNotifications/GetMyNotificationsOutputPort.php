<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyNotifications;

interface GetMyNotificationsOutputPort
{
    /**
     * @return list<array{notificationIdentifier: string, status: string, content: string, createdAt: string}>
     */
    public function notifications(): array;

    public function total(): int;
}
