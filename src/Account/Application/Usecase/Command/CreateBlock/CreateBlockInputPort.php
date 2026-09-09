<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateBlock;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface CreateBlockInputPort
{
    public function blockingAccountIdentifier(): AccountIdentifier;

    public function blockedAccountIdentifier(): AccountIdentifier;
}
