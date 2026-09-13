<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingAccounts;

interface GetFollowingAccountsOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, accountBio: ?string}> */
    public function accounts(): array;
}
