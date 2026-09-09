<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

interface GetAccountDetailsInputPort
{
    public function accountIdentifier(): string;
}
