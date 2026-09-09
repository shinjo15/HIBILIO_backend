<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveBlock;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class RemoveBlockInput implements RemoveBlockInputPort
{
    public function __construct(private AccountIdentifier $blockingAccountIdentifier, private AccountIdentifier $blockedAccountIdentifier) {}

    public function blockingAccountIdentifier(): AccountIdentifier
    {
        return $this->blockingAccountIdentifier;
    }

    public function blockedAccountIdentifier(): AccountIdentifier
    {
        return $this->blockedAccountIdentifier;
    }
}
