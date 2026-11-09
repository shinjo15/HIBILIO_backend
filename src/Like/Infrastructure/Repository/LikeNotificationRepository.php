<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Repository;

use App\Models\PersonalNotificationModel;
use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\Repository\LikeNotificationRepositoryInterface;

final class LikeNotificationRepository implements LikeNotificationRepositoryInterface
{
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
