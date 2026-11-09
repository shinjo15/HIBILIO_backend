<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Repository;

use App\Models\AccountModel;
use App\Models\PersonalNotificationModel;
use App\Models\PostModel;
use App\Models\RoutineExecutionModel;
use App\Models\RoutineModel;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Domain\Entity\SupportNotification;
use Src\Support\Domain\Repository\SupportNotificationRepositoryInterface;
use Src\Support\Domain\ValueObject\SupportNotificationTarget;

final class SupportNotificationRepository implements SupportNotificationRepositoryInterface
{
    public function findTarget(AccountIdentifier $actor, PostIdentifier $post): ?SupportNotificationTarget
    {
        $postModel = PostModel::query()->find($post->value());
        $execution = $postModel?->routine_execution_identifier === null ? null : RoutineExecutionModel::query()->find($postModel->routine_execution_identifier);
        $recipient = $execution === null ? null : AccountModel::query()->find($execution->executor_account_identifier);
        $routine = $execution === null ? null : RoutineModel::query()->find($execution->routine_identifier);
        $actorModel = AccountModel::query()->find($actor->value());
        if ($recipient === null || $routine === null || $actorModel === null) {
            return null;
        }

        return new SupportNotificationTarget(new AccountIdentifier($recipient->account_identifier), $actorModel->account_name, $routine->routine_name, (bool) $recipient->support_notifications_enabled);
    }

    public function save(SupportNotification $notification): void
    {
        PersonalNotificationModel::query()->create([
            'notification_identifier' => $notification->identifier()->value(),
            'receiver_account_identifier' => $notification->recipient()->value(),
            'content' => $notification->content()->value(),
            'status' => $notification->status()->value,
        ]);
    }
}
