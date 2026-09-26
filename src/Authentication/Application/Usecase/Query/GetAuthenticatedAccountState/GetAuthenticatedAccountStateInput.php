<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class GetAuthenticatedAccountStateInput implements GetAuthenticatedAccountStateInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
