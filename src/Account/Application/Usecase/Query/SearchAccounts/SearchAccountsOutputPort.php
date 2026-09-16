<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\SearchAccounts;

interface SearchAccountsOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, accountBio: ?string, iconImageUrl: ?string}> */
    public function accounts(): array;

    public function total(): int;
}
