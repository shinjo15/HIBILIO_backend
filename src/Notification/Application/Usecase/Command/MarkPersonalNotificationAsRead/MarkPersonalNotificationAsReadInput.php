<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Command\MarkPersonalNotificationAsRead;

use Src\Notification\Domain\ValueObject\NotificationIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class MarkPersonalNotificationAsReadInput implements MarkPersonalNotificationAsReadInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private NotificationIdentifier $notificationIdentifier) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function notificationIdentifier(): NotificationIdentifier
    {
        return $this->notificationIdentifier;
    }
}
