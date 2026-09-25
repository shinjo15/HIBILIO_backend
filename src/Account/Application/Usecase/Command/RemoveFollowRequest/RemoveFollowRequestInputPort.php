<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveFollowRequest;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface RemoveFollowRequestInputPort
{
    public function requestingAccountIdentifier(): AccountIdentifier;

    public function targetAccountIdentifier(): AccountIdentifier;
}
