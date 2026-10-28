<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

final readonly class GetRoutineDetailsOutput implements GetRoutineDetailsOutputPort
{
    /** @param array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}|null $routineDetails */
    public function __construct(
        private ?array $routineDetails,
    ) {}

    public function routineDetails(): ?array
    {
        return $this->routineDetails;
    }
}
