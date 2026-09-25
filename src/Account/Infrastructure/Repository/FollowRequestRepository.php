<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Repository;

use App\Models\FollowRequestModel;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Account\Domain\Entity\FollowRequest;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class FollowRequestRepository implements FollowRequestRepositoryInterface
{
    public function find(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        return $this->restore(FollowRequestModel::query()->where('requesting_account_identifier', $requestingAccountIdentifier->value())->where('target_account_identifier', $targetAccountIdentifier->value())->first());
    }

    public function findForUpdate(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        return $this->restore(FollowRequestModel::query()->where('requesting_account_identifier', $requestingAccountIdentifier->value())->where('target_account_identifier', $targetAccountIdentifier->value())->lockForUpdate()->first());
    }

    public function save(FollowRequest $followRequest): void
    {
        $values = ['status' => $followRequest->status()->value];
        $query = FollowRequestModel::query()->where('requesting_account_identifier', $followRequest->requestingAccountIdentifier()->value())->where('target_account_identifier', $followRequest->targetAccountIdentifier()->value());
        if ($query->update($values) === 0) {
            try {
                FollowRequestModel::query()->create(['requesting_account_identifier' => $followRequest->requestingAccountIdentifier()->value(), 'target_account_identifier' => $followRequest->targetAccountIdentifier()->value(), ...$values]);
            } catch (UniqueConstraintViolationException) {
                $query->update($values);
            }
        }
    }

    public function delete(FollowRequest $followRequest): void
    {
        FollowRequestModel::query()
            ->where('requesting_account_identifier', $followRequest->requestingAccountIdentifier()->value())
            ->where('target_account_identifier', $followRequest->targetAccountIdentifier()->value())
            ->delete();
    }

    private function restore(?FollowRequestModel $model): ?FollowRequest
    {
        return $model === null ? null : new FollowRequest(new AccountIdentifier($model->requesting_account_identifier), new AccountIdentifier($model->target_account_identifier), FollowRequestStatus::from($model->status));
    }
}
