<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingAccounts;

interface GetFollowingAccountsInputPort
{
    public function accountIdentifier(): string;
}
