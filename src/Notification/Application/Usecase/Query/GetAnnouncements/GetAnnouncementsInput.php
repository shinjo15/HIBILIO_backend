<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetAnnouncements;

final readonly class GetAnnouncementsInput implements GetAnnouncementsInputPort
{
    public function __construct(private int $page, private int $numberOfItemsPerPage) {}

    public function page(): int
    {
        return $this->page;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }
}
