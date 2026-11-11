<?php

declare(strict_types=1);

namespace Src\Routine\Domain\Entity;

use Src\Routine\Domain\ValueObject\RoutineActionMemo;
use Src\Routine\Domain\ValueObject\RoutineActionMinutes;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineActionPosition;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineAction
{
    private function __construct(
        private readonly RoutineActionIdentifier $routineActionIdentifier,
        private readonly ?RoutineActionIdentifier $parentRoutineActionIdentifier,
        private readonly RoutineIdentifier $routineIdentifier,
        private readonly RoutineActionName $routineActionName,
        private readonly ?RoutineActionMemo $routineActionMemo,
        private readonly ?RoutineActionMinutes $routineActionMinutes,
        private readonly RoutineActionPosition $position,
        private readonly ?RoutineVideoUrl $videoUrl,
    ) {}

    public static function create(
        RoutineActionIdentifier $routineActionIdentifier,
        ?RoutineActionIdentifier $parentRoutineActionIdentifier,
        RoutineIdentifier $routineIdentifier,
        RoutineActionName $routineActionName,
        ?RoutineActionMemo $routineActionMemo,
        ?RoutineActionMinutes $routineActionMinutes,
        RoutineActionPosition $position,
        ?RoutineVideoUrl $videoUrl = null,
    ): self {
        return new self(
            routineActionIdentifier: $routineActionIdentifier,
            parentRoutineActionIdentifier: $parentRoutineActionIdentifier,
            routineIdentifier: $routineIdentifier,
            routineActionName: $routineActionName,
            routineActionMemo: $routineActionMemo,
            routineActionMinutes: $routineActionMinutes,
            position: $position,
            videoUrl: $videoUrl,
        );
    }

    public function routineActionIdentifier(): RoutineActionIdentifier
    {
        return $this->routineActionIdentifier;
    }

    public function parentRoutineActionIdentifier(): ?RoutineActionIdentifier
    {
        return $this->parentRoutineActionIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function routineActionName(): RoutineActionName
    {
        return $this->routineActionName;
    }

    public function routineActionMemo(): ?RoutineActionMemo
    {
        return $this->routineActionMemo;
    }

    public function routineActionMinutes(): ?RoutineActionMinutes
    {
        return $this->routineActionMinutes;
    }

    public function position(): RoutineActionPosition
    {
        return $this->position;
    }

    public function videoUrl(): ?RoutineVideoUrl
    {
        return $this->videoUrl;
    }
}
