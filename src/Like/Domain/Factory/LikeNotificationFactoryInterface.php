<?php

declare(strict_types=1);

namespace Src\Like\Domain\Factory;

use Src\Like\Domain\Entity\LikeNotification;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface LikeNotificationFactoryInterface
{
    public function create(AccountIdentifier $recipient, string $actorName, string $routineName): LikeNotification;
}
