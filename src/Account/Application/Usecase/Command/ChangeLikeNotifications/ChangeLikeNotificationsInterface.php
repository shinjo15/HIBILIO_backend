<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeLikeNotifications;

interface ChangeLikeNotificationsInterface
{
    public function execute(ChangeLikeNotificationsInputPort $input): void;
}
