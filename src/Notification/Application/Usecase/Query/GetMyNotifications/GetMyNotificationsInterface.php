<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyNotifications;

interface GetMyNotificationsInterface
{
    public function execute(GetMyNotificationsInputPort $input): GetMyNotificationsOutputPort;
}
