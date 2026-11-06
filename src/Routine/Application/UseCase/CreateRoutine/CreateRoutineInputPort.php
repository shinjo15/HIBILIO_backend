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

interface CreateRoutineInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function parentRoutineIdentifier(): ?RoutineIdentifier;

    public function routineName(): RoutineName;

    /** @return list<CreateRoutineActionInput> */
    public function routineActions(): array;

    public function routineMemo(): ?RoutineMemo;

    public function routineExecutionMinutes(): ?RoutineExecutionMinutes;

    public function routineTagIdentifiers(): ?RoutineTagIdentifiers;

    public function videoUrl(): ?RoutineVideoUrl;
}
