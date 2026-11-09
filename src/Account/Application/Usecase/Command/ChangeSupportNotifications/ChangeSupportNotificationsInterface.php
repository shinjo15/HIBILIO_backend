<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeSupportNotifications;

interface ChangeSupportNotificationsInterface
{
    public function execute(ChangeSupportNotificationsInputPort $input): void;
}
