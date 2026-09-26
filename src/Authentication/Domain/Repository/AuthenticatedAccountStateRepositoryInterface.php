<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Repository;

use Src\Authentication\Domain\ValueObject\AuthenticatedAccountState;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AuthenticatedAccountStateRepositoryInterface
{
    public function find(AccountIdentifier $accountIdentifier): ?AuthenticatedAccountState;
}
