<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Command\MarkPersonalNotificationAsRead;

use Src\Notification\Domain\ValueObject\NotificationIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface MarkPersonalNotificationAsReadInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function notificationIdentifier(): NotificationIdentifier;
}
