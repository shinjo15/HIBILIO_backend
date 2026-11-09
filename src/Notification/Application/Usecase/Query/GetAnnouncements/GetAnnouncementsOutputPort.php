<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetAnnouncements;

interface GetAnnouncementsOutputPort
{
    /** @return list<array{announcementIdentifier: string, title: string, content: string, createdAt: string}> */
    public function announcements(): array;

    public function total(): int;
}
