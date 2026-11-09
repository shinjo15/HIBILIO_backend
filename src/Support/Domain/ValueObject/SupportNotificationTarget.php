<?php

declare(strict_types=1);

namespace Src\Support\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class SupportNotificationTarget
{
    public function __construct(private AccountIdentifier $recipient, private string $actorName, private string $routineName, private bool $notificationsEnabled) {}

    public function recipient(): AccountIdentifier
    {
        return $this->recipient;
    }

    public function actorName(): string
    {
        return $this->actorName;
    }

    public function routineName(): string
    {
        return $this->routineName;
    }

    public function notificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }
}
