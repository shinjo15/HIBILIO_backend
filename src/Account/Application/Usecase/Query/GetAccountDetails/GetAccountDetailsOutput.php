<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

final readonly class GetAccountDetailsOutput implements GetAccountDetailsOutputPort
{
    /** @param array<string, mixed>|null $accountDetails */
    public function __construct(
        private ?array $accountDetails,
    ) {}

    public function accountDetails(): ?array
    {
        return $this->accountDetails;
    }
}
