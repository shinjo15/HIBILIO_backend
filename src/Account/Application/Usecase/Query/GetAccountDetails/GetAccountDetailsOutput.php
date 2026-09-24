<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

final readonly class GetAccountDetailsOutput implements GetAccountDetailsOutputPort
{
    public function __construct(
        private AccountIdentity|AccountDetails|null $accountDetails,
    ) {}

    public function accountDetails(): AccountIdentity|AccountDetails|null
    {
        return $this->accountDetails;
    }
}
