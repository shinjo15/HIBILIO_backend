<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Repository;

use App\Models\AccountModel;
use App\Models\PostModel;
use App\Models\RoutineModel;
use Illuminate\Support\Facades\DB;
use Src\Like\Domain\Repository\LikeNotificationTargetRepositoryInterface;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class LikeNotificationTargetRepository implements LikeNotificationTargetRepositoryInterface
{
    public function find(AccountIdentifier $actor, PostIdentifier $post): ?LikeNotificationTarget
    {
        $postModel = PostModel::query()->where('post_identifier', $post->value())->first();
        $routine = $postModel === null ? null : RoutineModel::query()->where('routine_identifier', $postModel->routine_identifier)->first();
        if ($routine === null) {
            return null;
        }
        $author = AccountModel::query()->where('account_identifier', $routine->account_identifier)->lockForUpdate()->first();
        $actorModel = AccountModel::query()->where('account_identifier', $actor->value())->first();
        if ($author === null || $actorModel === null) {
            return null;
        }

        return new LikeNotificationTarget(
            new AccountIdentifier($author->account_identifier), $actorModel->account_name, $routine->routine_name,
            (bool) $author->like_notifications_enabled, (bool) $postModel->available, (bool) $routine->available,
            $author->available && $author->status === 'active', $actorModel->available && $actorModel->status === 'active',
            BlockVisibility::exists($actor->value(), $author->account_identifier), $author->visibility === 'private',
            $actor->value() === $author->account_identifier,
            DB::table('follows')->where('following_account_identifier', $actor->value())->where('followed_account_identifier', $author->account_identifier)->exists(),
        );
    }
}
