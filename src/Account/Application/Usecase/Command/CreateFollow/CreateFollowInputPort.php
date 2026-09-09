<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateFollow;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface CreateFollowInputPort
{
    public function followingAccountIdentifier(): AccountIdentifier;

    public function followedAccountIdentifier(): AccountIdentifier;
}
