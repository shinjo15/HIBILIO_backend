<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingAccounts;

interface GetFollowingAccountsInterface
{
    public function execute(GetFollowingAccountsInputPort $input): GetFollowingAccountsOutputPort;
}
