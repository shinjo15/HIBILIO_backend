<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFollowingAccounts;

final readonly class GetFollowingAccountsOutput implements GetFollowingAccountsOutputPort
{
    /** @param list<array{accountIdentifier: string, accountName: string, accountBio: ?string}> $accounts */
    public function __construct(private array $accounts) {}

    public function accounts(): array
    {
        return $this->accounts;
    }
}
