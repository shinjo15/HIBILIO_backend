<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeSupportNotifications;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeSupportNotificationsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function enabled(): bool;
}
