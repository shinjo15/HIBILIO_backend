<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Repository;

use App\Models\AccountModel;
use App\Models\PersonalNotificationModel;
use App\Models\RoutineModel;
use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\Repository\LikeNotificationRepositoryInterface;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class LikeNotificationRepository implements LikeNotificationRepositoryInterface
{
    public function findTarget(AccountIdentifier $actor, RoutineIdentifier $routine): ?LikeNotificationTarget
    {
        $routineModel = RoutineModel::query()->with('account')->find($routine->value());
        $author = $routineModel?->account;
        $actorModel = AccountModel::query()->find($actor->value());
        if ($author === null || $actorModel === null) {
            return null;
        }

        return new LikeNotificationTarget(
            new AccountIdentifier($author->account_identifier),
            $actorModel->account_name,
            $routineModel->routine_name,
            (bool) $author->like_notifications_enabled,
        );
    }

    public function save(LikeNotification $notification): void
    {
        PersonalNotificationModel::query()->create([
            'notification_identifier' => $notification->identifier()->value(),
            'receiver_account_identifier' => $notification->recipient()->value(),
            'content' => $notification->content()->value(),
            'status' => $notification->status()->value,
        ]);
    }
}
