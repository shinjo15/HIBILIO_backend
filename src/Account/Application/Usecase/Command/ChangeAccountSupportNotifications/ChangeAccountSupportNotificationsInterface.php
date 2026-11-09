<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountSupportNotifications;

interface ChangeAccountSupportNotificationsInterface
{
    public function execute(ChangeAccountSupportNotificationsInputPort $input): void;
}
