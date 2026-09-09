<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountVisibility;

use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ChangeAccountVisibilityInput implements ChangeAccountVisibilityInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private AccountVisibility $visibility) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function visibility(): AccountVisibility
    {
        return $this->visibility;
    }
}
