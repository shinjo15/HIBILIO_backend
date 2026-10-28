<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizationOrigins;

interface GetCustomizationOriginsOutputPort
{
    /** @return array<string, array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}> */
    public function originsByRoutineIdentifier(): array;
}
