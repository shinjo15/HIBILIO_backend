<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Repository;

use App\Models\PersonalNotificationModel;
use Src\Notification\Domain\Entity\PersonalNotification;
use Src\Notification\Domain\Repository\PersonalNotificationRepositoryInterface;
use Src\Notification\Domain\ValueObject\NotificationIdentifier;
use Src\Notification\Domain\ValueObject\PersonalNotificationContent;
use Src\Notification\Domain\ValueObject\PersonalNotificationStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class PersonalNotificationRepository implements PersonalNotificationRepositoryInterface
{
    public function find(NotificationIdentifier $notificationIdentifier): ?PersonalNotification
    {
        $model = PersonalNotificationModel::query()->where('notification_identifier', $notificationIdentifier->value())->lockForUpdate()->first();

        return $model === null ? null : $this->restore($model);
    }

    public function save(PersonalNotification $notification): void
    {
        PersonalNotificationModel::query()
            ->where('notification_identifier', $notification->notificationIdentifier()->value())
            ->update(['status' => $notification->status()->value]);
    }

    private function restore(PersonalNotificationModel $model): PersonalNotification
    {
        return new PersonalNotification(
            new NotificationIdentifier($model->notification_identifier),
            new AccountIdentifier($model->receiver_account_identifier),

            new PersonalNotificationContent($model->content),
            PersonalNotificationStatus::from($model->status),
        );
    }
}
