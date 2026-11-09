<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetAnnouncements;

final readonly class GetAnnouncementsOutput implements GetAnnouncementsOutputPort
{
    /** @param list<array{announcementIdentifier: string, title: string, content: string, createdAt: string}> $announcements */
    public function __construct(private array $announcements, private int $total) {}

    public function announcements(): array
    {
        return $this->announcements;
    }

    public function total(): int
    {
        return $this->total;
    }
}
