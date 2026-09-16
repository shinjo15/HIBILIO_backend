<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

interface SearchRoutinesInputPort
{
    public function accountIdentifier(): ?string;

    public function title(): ?string;

    /** @return list<string> */
    public function tagIdentifiers(): array;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
