<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Command\MarkPersonalNotificationAsRead;

interface MarkPersonalNotificationAsReadInterface
{
    public function execute(MarkPersonalNotificationAsReadInputPort $input): void;
}
