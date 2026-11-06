<?php

declare(strict_types=1);

namespace Src\Routine\Application\UseCase\UpdateRoutineVideoLink;

use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class UpdateRoutineVideoLinkInput implements UpdateRoutineVideoLinkInputPort
{
    public function __construct(
        private RoutineIdentifier $routineIdentifier,
        private AccountIdentifier $accountIdentifier,
        private ?RoutineActionIdentifier $routineActionIdentifier,
        private bool $videoUrlProvided,
        private ?RoutineVideoUrl $videoUrl,
    ) {}

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function routineActionIdentifier(): ?RoutineActionIdentifier
    {
        return $this->routineActionIdentifier;
    }

    public function videoUrlProvided(): bool
    {
        return $this->videoUrlProvided;
    }

    public function videoUrl(): ?RoutineVideoUrl
    {
        return $this->videoUrl;
    }
}
