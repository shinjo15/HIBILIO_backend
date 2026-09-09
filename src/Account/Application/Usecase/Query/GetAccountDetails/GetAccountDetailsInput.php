<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

final readonly class GetAccountDetailsInput implements GetAccountDetailsInputPort
{
    public function __construct(
        private string $accountIdentifier,
    ) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }
}
