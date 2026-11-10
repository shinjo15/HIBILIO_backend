<?php

declare(strict_types=1);

namespace Src\Account\Domain\Repository;

use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AccountRepositoryInterface
{
    public function find(AccountIdentifier $accountIdentifier): ?Account;

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account;

    public function save(Account $account): void;

    public function updateProfile(Account $account): void;
}
