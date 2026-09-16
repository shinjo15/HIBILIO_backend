<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\SearchAccounts;

interface SearchAccountsInputPort
{
    public function accountIdentifier(): ?string;

    public function accountName(): ?string;

    /** @return list<string> */
    public function tagIdentifiers(): array;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
