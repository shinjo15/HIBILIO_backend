<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Factory;

use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Factory\RoutineActionFactoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionMemo;
use Src\Routine\Domain\ValueObject\RoutineActionMinutes;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineActionFactory implements RoutineActionFactoryInterface
{
    public function create(
        RoutineActionIdentifier $routineActionIdentifier,
        ?RoutineActionIdentifier $parentRoutineActionIdentifier,
        RoutineIdentifier $routineIdentifier,
        RoutineActionName $routineActionName,
        ?RoutineActionMemo $routineActionMemo,
        ?RoutineActionMinutes $routineActionMinutes,
        ?RoutineVideoUrl $videoUrl = null,
    ): RoutineAction {
        return RoutineAction::create(
            $routineActionIdentifier,
            $parentRoutineActionIdentifier,
            $routineIdentifier,
            $routineActionName,
            $routineActionMemo,
            $routineActionMinutes,
            $videoUrl,
        );
    }
}
