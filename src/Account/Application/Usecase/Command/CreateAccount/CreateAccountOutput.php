<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateAccount;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CreateAccountOutput implements CreateAccountOutputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
