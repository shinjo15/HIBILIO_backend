<?php

declare(strict_types=1);

namespace Src\Support\Domain\Entity;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Support\Domain\ValueObject\SupportNotificationContent;
use Src\Support\Domain\ValueObject\SupportNotificationIdentifier;
use Src\Support\Domain\ValueObject\SupportNotificationStatus;

final readonly class SupportNotification
{
    public function __construct(private SupportNotificationIdentifier $identifier, private AccountIdentifier $recipient, private SupportNotificationContent $content, private SupportNotificationStatus $status) {}

    public function identifier(): SupportNotificationIdentifier
    {
        return $this->identifier;
    }

    public function recipient(): AccountIdentifier
    {
        return $this->recipient;
    }

    public function content(): SupportNotificationContent
    {
        return $this->content;
    }

    public function status(): SupportNotificationStatus
    {
        return $this->status;
    }
}
