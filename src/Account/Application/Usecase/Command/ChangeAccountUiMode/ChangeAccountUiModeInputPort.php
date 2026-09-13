<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountUiMode;

use Src\Account\Domain\ValueObject\AccountUiMode;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeAccountUiModeInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function uiMode(): AccountUiMode;
}
