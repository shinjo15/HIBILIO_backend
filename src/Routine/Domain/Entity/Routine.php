<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Entity;

use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class Routine
{
    private function __construct(
        private readonly RoutineIdentifier $routineIdentifier,
        private readonly ?RoutineIdentifier $parentRoutineIdentifier,
        private readonly RoutineName $routineName,
        private readonly RoutineActionIdentifiers $routineActionIdentifiers,
        private readonly ?RoutineMemo $routineMemo,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly ?RoutineExecutionMinutes $routineExecutionMinutes,
        private readonly ?RoutineTagIdentifiers $routineTagIdentifiers,
        private readonly ?RoutineVideoUrl $videoUrl,
    ) {}

    public static function create(
        RoutineIdentifier $routineIdentifier,
        ?RoutineIdentifier $parentRoutineIdentifier,
        RoutineName $routineName,
        RoutineActionIdentifiers $routineActionIdentifiers,
        ?RoutineMemo $routineMemo,
        AccountIdentifier $accountIdentifier,
        ?RoutineExecutionMinutes $routineExecutionMinutes,
        ?RoutineTagIdentifiers $routineTagIdentifiers,
        ?RoutineVideoUrl $videoUrl = null,
    ): self {
        $videoUrl?->validateForRegistration();

        return new self(
            routineIdentifier: $routineIdentifier,
            parentRoutineIdentifier: $parentRoutineIdentifier,
            routineName: $routineName,
            routineActionIdentifiers: $routineActionIdentifiers,
            routineMemo: $routineMemo,
            accountIdentifier: $accountIdentifier,
            routineExecutionMinutes: $routineExecutionMinutes,
            routineTagIdentifiers: $routineTagIdentifiers,
            videoUrl: $videoUrl,
        );
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function parentRoutineIdentifier(): ?RoutineIdentifier
    {
        return $this->parentRoutineIdentifier;
    }

    public function routineName(): RoutineName
    {
        return $this->routineName;
    }

    public function routineActionIdentifiers(): RoutineActionIdentifiers
    {
        return $this->routineActionIdentifiers;
    }

    public function routineMemo(): ?RoutineMemo
    {
        return $this->routineMemo;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function routineExecutionMinutes(): ?RoutineExecutionMinutes
    {
        return $this->routineExecutionMinutes;
    }

    public function routineTagIdentifiers(): ?RoutineTagIdentifiers
    {
        return $this->routineTagIdentifiers;
    }

    public function videoUrl(): ?RoutineVideoUrl
    {
        return $this->videoUrl;
    }
}
