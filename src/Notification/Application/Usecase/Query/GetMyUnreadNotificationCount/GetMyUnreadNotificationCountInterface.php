<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount;

interface GetMyUnreadNotificationCountInterface
{
    public function execute(GetMyUnreadNotificationCountInputPort $input): GetMyUnreadNotificationCountOutputPort;
}
