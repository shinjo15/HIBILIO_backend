<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Repository;

use App\Models\LikeModel;
use Src\Like\Domain\Entity\Like;
use Src\Like\Domain\Repository\LikeRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

final class LikeRepository implements LikeRepositoryInterface
{
    public function exists(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): bool
    {
        return LikeModel::query()->where('account_identifier', $accountIdentifier->value())->where('post_identifier', $postIdentifier->value())->exists();
    }

    public function delete(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): void
    {
        LikeModel::query()->where('account_identifier', $accountIdentifier->value())->where('post_identifier', $postIdentifier->value())->delete();
    }

    public function save(Like $like): void
    {
        LikeModel::query()->create(['account_identifier' => $like->accountIdentifier()->value(), 'post_identifier' => $like->postIdentifier()->value()]);
    }
}
