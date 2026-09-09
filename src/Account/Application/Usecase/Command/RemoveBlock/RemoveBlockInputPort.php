<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveBlock;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface RemoveBlockInputPort
{
    public function blockingAccountIdentifier(): AccountIdentifier;

    public function blockedAccountIdentifier(): AccountIdentifier;
}
