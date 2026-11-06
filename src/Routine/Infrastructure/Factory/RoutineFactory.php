<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Factory;

use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Factory\RoutineFactoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineFactory implements RoutineFactoryInterface
{
    public function create(
        RoutineIdentifier $routineIdentifier,
        ?RoutineIdentifier $parentRoutineIdentifier,
        RoutineName $routineName,
        RoutineActionIdentifiers $routineActionIdentifiers,
        ?RoutineMemo $routineMemo,
        AccountIdentifier $accountIdentifier,
        ?RoutineExecutionMinutes $routineExecutionMinutes,
        ?RoutineTagIdentifiers $routineTagIdentifiers,
        ?RoutineVideoUrl $videoUrl = null,
    ): Routine {
        return Routine::create(
            $routineIdentifier,
            $parentRoutineIdentifier,
            $routineName,
            $routineActionIdentifiers,
            $routineMemo,
            $accountIdentifier,
            $routineExecutionMinutes,
            $routineTagIdentifiers,
            $videoUrl,
        );
    }
}
