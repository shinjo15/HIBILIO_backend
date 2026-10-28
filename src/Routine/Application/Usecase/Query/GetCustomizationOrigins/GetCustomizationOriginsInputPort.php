<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizationOrigins;

interface GetCustomizationOriginsInputPort
{
    /** @return list<string> */
    public function routineIdentifiers(): array;

    public function viewerAccountIdentifier(): ?string;
}
