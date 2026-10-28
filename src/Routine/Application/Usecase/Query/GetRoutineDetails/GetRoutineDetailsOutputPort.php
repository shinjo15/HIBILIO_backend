<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetRoutineDetails;

interface GetRoutineDetailsOutputPort
{
    /** @return array{customizationOrigin: array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null, ...}|null */
    public function routineDetails(): ?array;
}
