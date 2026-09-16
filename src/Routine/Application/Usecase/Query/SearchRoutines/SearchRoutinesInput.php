<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\SearchRoutines;

final readonly class SearchRoutinesInput implements SearchRoutinesInputPort
{
    /** @param list<string> $tagIdentifiers */
    public function __construct(
        private ?string $accountIdentifier,
        private ?string $title,
        private array $tagIdentifiers,
        private int $page,
        private int $numberOfItemsPerPage,
    ) {}

    public function accountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function tagIdentifiers(): array
    {
        return $this->tagIdentifiers;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }
}
