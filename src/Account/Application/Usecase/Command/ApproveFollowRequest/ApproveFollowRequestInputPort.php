<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ApproveFollowRequest;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ApproveFollowRequestInputPort
{
    public function targetAccountIdentifier(): AccountIdentifier;

    public function requestingAccountIdentifier(): AccountIdentifier;
}
