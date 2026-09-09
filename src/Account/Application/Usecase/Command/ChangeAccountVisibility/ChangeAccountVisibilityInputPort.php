<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountVisibility;

use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeAccountVisibilityInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function visibility(): AccountVisibility;
}
