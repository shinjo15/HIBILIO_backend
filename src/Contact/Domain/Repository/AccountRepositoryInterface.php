<?php

declare(strict_types=1);

namespace Src\Contact\Domain\Repository;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AccountRepositoryInterface
{
    public function findEmailAddress(AccountIdentifier $accountIdentifier): ?EmailAddress;
}
