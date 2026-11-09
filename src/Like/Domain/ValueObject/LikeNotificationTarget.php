<?php

declare(strict_types=1);

namespace Src\Like\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LikeNotificationTarget
{
    public function __construct(
        private AccountIdentifier $recipient,
        private string $actorName,
        private string $routineName,
        private bool $enabled,
        private bool $postAvailable,
        private bool $routineAvailable,
        private bool $authorAvailable,
        private bool $actorAvailable,
        private bool $blocked,
        private bool $authorPrivate,
        private bool $ownPost,
        private bool $followsAuthor,
    ) {}

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

    public function canNotify(): bool
    {
        return $this->enabled && $this->postAvailable && $this->routineAvailable && $this->authorAvailable && $this->actorAvailable
            && ! $this->blocked && (! $this->authorPrivate || $this->ownPost || $this->followsAuthor);
    }
}
