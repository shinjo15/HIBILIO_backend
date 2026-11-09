<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountSupportNotifications;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ChangeAccountSupportNotificationsInput implements ChangeAccountSupportNotificationsInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private bool $enabled) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }
}
