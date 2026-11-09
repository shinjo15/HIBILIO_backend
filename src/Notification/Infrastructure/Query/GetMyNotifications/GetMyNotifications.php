<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Query\GetMyNotifications;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInputPort;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInterface;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsOutput;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsOutputPort;

final class GetMyNotifications implements GetMyNotificationsInterface
{
    public function execute(GetMyNotificationsInputPort $input): GetMyNotificationsOutputPort
    {
        $paginator = DB::table('personal_notifications')
            ->where('receiver_account_identifier', $input->accountIdentifier())
            ->orderByDesc('created_at')
            ->orderBy('notification_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());
        $notifications = $paginator->getCollection()
            ->map(static fn (object $record): array => [
                'notificationIdentifier' => (string) $record->notification_identifier,
                'status' => (string) $record->status,
                'content' => (string) $record->content,
                'createdAt' => (new DateTimeImmutable((string) $record->created_at))->format(DATE_ATOM),
            ])
            ->values()
            ->all();

        return new GetMyNotificationsOutput($notifications, $paginator->total());
    }
}
