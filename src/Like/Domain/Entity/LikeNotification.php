<?php

declare(strict_types=1);

namespace Src\Like\Domain\Entity;

use Src\Like\Domain\ValueObject\LikeNotificationContent;
use Src\Like\Domain\ValueObject\LikeNotificationIdentifier;
use Src\Like\Domain\ValueObject\LikeNotificationStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LikeNotification
{
    public function __construct(private LikeNotificationIdentifier $identifier, private AccountIdentifier $recipient, private LikeNotificationContent $content, private LikeNotificationStatus $status) {}

    public function identifier(): LikeNotificationIdentifier
    {
        return $this->identifier;
    }

    public function recipient(): AccountIdentifier
    {
        return $this->recipient;
    }

    public function content(): LikeNotificationContent
    {
        return $this->content;
    }

    public function status(): LikeNotificationStatus
    {
        return $this->status;
    }
}
