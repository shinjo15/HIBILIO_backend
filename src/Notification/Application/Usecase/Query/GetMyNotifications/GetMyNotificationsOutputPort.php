<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyNotifications;

interface GetMyNotificationsOutputPort
{
    /**
     * @return list<array{notificationIdentifier: string, category: string, receiverAccountIdentifier: ?string, status: ?string, title: ?string, content: string, createdAt: string}>
     */
    public function notifications(): array;

    public function total(): int;
}
