<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountSupportNotifications;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeAccountSupportNotificationsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function enabled(): bool;
}
