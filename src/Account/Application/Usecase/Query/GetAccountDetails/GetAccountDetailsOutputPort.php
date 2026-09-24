<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

interface GetAccountDetailsOutputPort
{
    /**
     * null: unavailable; AccountIdentity: identity only; AccountDetails: full details.
     */
    public function accountDetails(): AccountIdentity|AccountDetails|null;
}
