<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\SearchAccounts;

final readonly class SearchAccountsInput implements SearchAccountsInputPort
{
    /** @param list<string> $tagIdentifiers */
    public function __construct(
        private ?string $accountIdentifier,
        private ?string $accountName,
        private array $tagIdentifiers,
        private int $page,
        private int $numberOfItemsPerPage,
    ) {}

    public function accountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function accountName(): ?string
    {
        return $this->accountName;
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
