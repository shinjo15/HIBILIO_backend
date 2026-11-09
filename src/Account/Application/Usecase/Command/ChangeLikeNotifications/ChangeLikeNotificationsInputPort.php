<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeLikeNotifications;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeLikeNotificationsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function enabled(): bool;
}
