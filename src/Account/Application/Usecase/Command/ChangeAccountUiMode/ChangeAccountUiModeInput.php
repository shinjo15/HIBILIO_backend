<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountUiMode;

use Src\Account\Domain\ValueObject\AccountUiMode;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ChangeAccountUiModeInput implements ChangeAccountUiModeInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private AccountUiMode $uiMode) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function uiMode(): AccountUiMode
    {
        return $this->uiMode;
    }
}
