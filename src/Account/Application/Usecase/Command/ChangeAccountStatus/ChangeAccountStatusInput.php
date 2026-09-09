<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountStatus;

use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ChangeAccountStatusInput implements ChangeAccountStatusInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private AccountStatus $status,
    ) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }
}
