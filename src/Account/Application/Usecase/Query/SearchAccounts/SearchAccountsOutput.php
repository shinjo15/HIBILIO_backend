<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\SearchAccounts;

final readonly class SearchAccountsOutput implements SearchAccountsOutputPort
{
    /** @param list<array{accountIdentifier: string, accountName: string, accountBio: ?string, iconImageUrl: ?string}> $accounts */
    public function __construct(private array $accounts, private int $total) {}

    public function accounts(): array
    {
        return $this->accounts;
    }

    public function total(): int
    {
        return $this->total;
    }
}
