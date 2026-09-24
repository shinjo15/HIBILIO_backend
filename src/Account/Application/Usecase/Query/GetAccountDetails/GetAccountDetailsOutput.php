<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

/** @phpstan-import-type AccountDetails from GetAccountDetailsOutputPort */
final readonly class GetAccountDetailsOutput implements GetAccountDetailsOutputPort
{
    /** @param AccountDetails|null $accountDetails */
    public function __construct(
        private ?array $accountDetails,
    ) {}

    /** @return AccountDetails|null */
    public function accountDetails(): ?array
    {
        return $this->accountDetails;
    }
}
