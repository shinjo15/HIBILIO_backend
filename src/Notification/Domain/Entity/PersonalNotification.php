<?php

declare(strict_types=1);

namespace Src\Notification\Domain\Entity;

use Src\Notification\Domain\Exception\PersonalNotificationAccessDeniedException;
use Src\Notification\Domain\ValueObject\NotificationIdentifier;
use Src\Notification\Domain\ValueObject\PersonalNotificationStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class PersonalNotification
{
    public function __construct(
        private readonly NotificationIdentifier $notificationIdentifier,
        private readonly AccountIdentifier $receiverAccountIdentifier,

        private readonly string $content,
        private PersonalNotificationStatus $status,
    ) {}

    public function markAsReadBy(AccountIdentifier $accountIdentifier): void
    {
        if ($this->receiverAccountIdentifier->value() !== $accountIdentifier->value()) {
            throw new PersonalNotificationAccessDeniedException;
        }

        $this->status = PersonalNotificationStatus::Read;
    }

    public function notificationIdentifier(): NotificationIdentifier
    {
        return $this->notificationIdentifier;
    }

    public function receiverAccountIdentifier(): AccountIdentifier
    {
        return $this->receiverAccountIdentifier;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function status(): PersonalNotificationStatus
    {
        return $this->status;
    }
}
