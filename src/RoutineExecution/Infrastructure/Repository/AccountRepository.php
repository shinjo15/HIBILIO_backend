<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\AccountModel;
use Illuminate\Support\Facades\DB;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\RoutineExecution\Domain\Entity\Account;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class AccountRepository implements AccountRepositoryInterface
{
    public function findForUpdate(AccountIdentifier $accountIdentifier): ?Account
    {
        $model = AccountModel::query()->where('account_identifier', $accountIdentifier->value())->lockForUpdate()->first();

        return $model === null ? null : new Account(new AccountIdentifier($model->account_identifier), (int) $model->experience, AccountStatus::from($model->status), $model->available);
    }

    public function save(Account $account): void
    {
        DB::table('accounts')->where('account_identifier', $account->accountIdentifier()->value())->update(['experience' => $account->experience()]);
    }
}
