<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use App\Models\BlockModel;
use App\Models\FollowModel;
use App\Models\RoutineModel;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\RoutineExecution\Domain\Repository\ExecutableRoutineRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutableRoutine;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class ExecutableRoutineRepository implements ExecutableRoutineRepositoryInterface
{
    public function find(RoutineIdentifier $routineIdentifier, AccountIdentifier $viewer): ?ExecutableRoutine
    {
        $routine = RoutineModel::query()->with('account')->find($routineIdentifier->value());
        if ($routine === null) {
            return null;
        }

        $author = $routine->account;
        if ($author === null) {
            return new ExecutableRoutine(new AccountIdentifier($routine->account_identifier), $viewer, $routine->available, false, null, null, false, false, false);
        }

        return new ExecutableRoutine(
            new AccountIdentifier($author->account_identifier),
            $viewer,
            $routine->available,
            $author->available,
            AccountStatus::from($author->status),
            AccountVisibility::from($author->visibility),
            FollowModel::query()->where('following_account_identifier', $viewer->value())->where('followed_account_identifier', $author->account_identifier)->exists(),
            BlockModel::query()->where('blocking_account_identifier', $viewer->value())->where('blocked_account_identifier', $author->account_identifier)->exists(),
            BlockModel::query()->where('blocking_account_identifier', $author->account_identifier)->where('blocked_account_identifier', $viewer->value())->exists(),
        );
    }
}
