<?php

declare(strict_types=1);

namespace Src\Account\Domain\Repository;

use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AccountRepositoryInterface
{
    public function find(AccountIdentifier $accountIdentifier): ?Account;

    /** Must be called within the transaction that saves the account's experience. */
    public function findForUpdate(AccountIdentifier $accountIdentifier): ?Account;

    /** Persist only experience from an account obtained by findForUpdate in the same transaction. */
    public function saveExperience(Account $account): void;

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account;

    /** On creation, persist all account state including experience; on update, leave experience unchanged. */
    public function save(Account $account): void;

    public function updateProfile(Account $account): void;
}
