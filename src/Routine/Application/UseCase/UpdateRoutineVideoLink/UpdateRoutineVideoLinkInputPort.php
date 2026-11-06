<?php

declare(strict_types=1);

namespace Src\Routine\Application\UseCase\UpdateRoutineVideoLink;

use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

interface UpdateRoutineVideoLinkInputPort
{
    public function routineIdentifier(): RoutineIdentifier;

    public function accountIdentifier(): AccountIdentifier;

    public function routineActionIdentifier(): ?RoutineActionIdentifier;

    public function videoUrlProvided(): bool;

    public function videoUrl(): ?RoutineVideoUrl;
}
