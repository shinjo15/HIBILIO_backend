<?php

declare(strict_types=1);

namespace Src\Contact\Infrastructure\Repository;

use App\Models\AccountModel;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Contact\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AccountRepository implements AccountRepositoryInterface
{
    public function findEmailAddress(AccountIdentifier $accountIdentifier): ?EmailAddress
    {
        $emailAddress = AccountModel::query()
            ->where('account_identifier', $accountIdentifier->value())
            ->value('email_address');

        return $emailAddress === null ? null : new EmailAddress($emailAddress);
    }
}
