<?php

declare(strict_types=1);

namespace Src\Routine\Application\UseCase\CreateRoutine;

use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class CreateRoutineInput implements CreateRoutineInputPort
{
    /** @param list<CreateRoutineActionInput> $routineActions */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private ?RoutineIdentifier $parentRoutineIdentifier,
        private RoutineName $routineName,
        private array $routineActions,
        private ?RoutineMemo $routineMemo,
        private ?RoutineExecutionMinutes $routineExecutionMinutes,
        private ?RoutineTagIdentifiers $routineTagIdentifiers,
        private ?RoutineVideoUrl $videoUrl = null,
    ) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function parentRoutineIdentifier(): ?RoutineIdentifier
    {
        return $this->parentRoutineIdentifier;
    }

    public function routineName(): RoutineName
    {
        return $this->routineName;
    }

    public function routineActions(): array
    {
        return $this->routineActions;
    }

    public function routineMemo(): ?RoutineMemo
    {
        return $this->routineMemo;
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
