<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface GetAuthenticatedAccountStateInputPort
{
    public function accountIdentifier(): AccountIdentifier;
}
