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
        private ?RoutineVideoUrl $videoUrl,
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
        if ($routineActionIdentifiers->values() === []) {
            throw new \InvalidArgumentException('ルーティンには少なくとも1つの行動が必要です。');
        }

        return self::reconstitute($routineIdentifier, $parentRoutineIdentifier, $routineName, $routineActionIdentifiers, $routineMemo, $accountIdentifier, $routineExecutionMinutes, $routineTagIdentifiers, $videoUrl);
    }

    public static function reconstitute(
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

    public function isOwnedBy(AccountIdentifier $accountIdentifier): bool
    {
        return strcasecmp($this->accountIdentifier->value(), $accountIdentifier->value()) === 0;
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

    public function changeVideoUrl(?RoutineVideoUrl $videoUrl): void
    {
        $this->videoUrl = $videoUrl;
    }
}
