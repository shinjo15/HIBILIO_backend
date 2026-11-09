<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Query\GetAnnouncements;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Notification\Application\Usecase\Query\GetAnnouncements\GetAnnouncementsInputPort;
use Src\Notification\Application\Usecase\Query\GetAnnouncements\GetAnnouncementsInterface;
use Src\Notification\Application\Usecase\Query\GetAnnouncements\GetAnnouncementsOutput;
use Src\Notification\Application\Usecase\Query\GetAnnouncements\GetAnnouncementsOutputPort;

final class GetAnnouncements implements GetAnnouncementsInterface
{
    public function execute(GetAnnouncementsInputPort $input): GetAnnouncementsOutputPort
    {
        $paginator = DB::table('system_announcements')->orderByDesc('created_at')->orderBy('announcement_identifier')
            ->paginate($input->numberOfItemsPerPage(), ['*'], 'page', $input->page());
        $announcements = $paginator->getCollection()->map(static fn (object $record): array => [
            'announcementIdentifier' => (string) $record->announcement_identifier,
            'title' => (string) $record->title,
            'content' => (string) $record->content,
            'createdAt' => (new DateTimeImmutable((string) $record->created_at))->format(DATE_ATOM),
        ])->values()->all();

        return new GetAnnouncementsOutput($announcements, $paginator->total());
    }
}
