<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

interface SearchRoutinesOutputPort
{
    /** @return list<array{itemType: string, routineIdentifier: string, routineExecutionIdentifier: ?string, routineName: string, accountIdentifier: string, accountName: string, iconImageUrl: ?string, tags: list<array{tagIdentifier: string, tagName: string}>, publishedAt: string}> */
    public function items(): array;

    public function total(): int;
}
