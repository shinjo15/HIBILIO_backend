<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Authentication\Domain\Repository\AuthenticatedAccountStateRepositoryInterface;
use Src\Authentication\Domain\ValueObject\AuthenticatedAccountState;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AuthenticatedAccountStateRepository implements AuthenticatedAccountStateRepositoryInterface
{
    public function find(AccountIdentifier $accountIdentifier): ?AuthenticatedAccountState
    {
        $state = DB::table('accounts')->where('account_identifier', $accountIdentifier->value())->first(['available', 'status']);

        return $state === null ? null : new AuthenticatedAccountState((bool) $state->available, AccountStatus::from($state->status));
    }
}
