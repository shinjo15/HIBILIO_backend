<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetAnnouncements;

interface GetAnnouncementsInputPort
{
    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
