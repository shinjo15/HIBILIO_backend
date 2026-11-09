<?php

declare(strict_types=1);

namespace Src\Support\Domain\Factory;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Support\Domain\Entity\SupportNotification;

interface SupportNotificationFactoryInterface
{
    public function create(AccountIdentifier $recipient, string $actorName, string $routineName): SupportNotification;
}
