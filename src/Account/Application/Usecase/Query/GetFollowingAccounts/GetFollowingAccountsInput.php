<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingAccounts;

final readonly class GetFollowingAccountsInput implements GetFollowingAccountsInputPort
{
    public function __construct(private string $accountIdentifier) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }
}
