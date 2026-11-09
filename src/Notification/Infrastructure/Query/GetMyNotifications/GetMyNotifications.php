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
        $personalNotifications = DB::table('personal_notifications as notifications')
            ->where('notifications.receiver_account_identifier', $input->accountIdentifier())
            ->select([
                'notifications.notification_identifier',
                DB::raw("'personal' as category"),
                'notifications.receiver_account_identifier',
                'notifications.status',
                DB::raw('NULL as title'),
                'notifications.content',
                'notifications.created_at',
            ]);
        $systemAnnouncements = DB::table('system_announcements')
            ->select([
                'announcement_identifier as notification_identifier',
                DB::raw("'system_announcement' as category"),
                DB::raw('NULL as receiver_account_identifier'),
                DB::raw('NULL as status'),
                'title',
                'content',
                'created_at',
            ]);

        $paginator = DB::query()
            ->fromSub($personalNotifications->unionAll($systemAnnouncements), 'notification_inbox')
            ->orderByDesc('created_at')
            ->orderBy('category')
            ->orderBy('notification_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());
        $notifications = $paginator->getCollection()
            ->map(static fn (object $record): array => [
                'notificationIdentifier' => (string) $record->notification_identifier,
                'category' => (string) $record->category,
                'receiverAccountIdentifier' => $record->receiver_account_identifier === null ? null : (string) $record->receiver_account_identifier,
                'status' => $record->status === null ? null : (string) $record->status,
                'title' => $record->title === null ? null : (string) $record->title,
                'content' => (string) $record->content,
                'createdAt' => (new DateTimeImmutable((string) $record->created_at))->format(DATE_ATOM),
            ])
            ->values()
            ->all();

        return new GetMyNotificationsOutput($notifications, $paginator->total());
    }
}
