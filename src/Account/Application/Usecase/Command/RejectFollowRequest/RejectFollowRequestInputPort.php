<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RejectFollowRequest;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface RejectFollowRequestInputPort
{
    public function targetAccountIdentifier(): AccountIdentifier;

    public function requestingAccountIdentifier(): AccountIdentifier;
}
