<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount;

interface GetMyUnreadNotificationCountInputPort
{
    public function accountIdentifier(): string;
}
