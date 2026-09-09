<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

interface GetAccountDetailsOutputPort
{
    /** @return array<string, mixed>|null */
    public function accountDetails(): ?array;
}
