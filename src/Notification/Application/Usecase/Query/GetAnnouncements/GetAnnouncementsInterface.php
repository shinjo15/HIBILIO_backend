<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetAnnouncements;

interface GetAnnouncementsInterface
{
    public function execute(GetAnnouncementsInputPort $input): GetAnnouncementsOutputPort;
}
