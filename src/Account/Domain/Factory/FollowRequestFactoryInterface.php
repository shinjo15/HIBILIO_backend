<?php

declare(strict_types=1);

namespace Src\Account\Domain\Factory;

use Src\Account\Domain\Entity\FollowRequest;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface FollowRequestFactoryInterface
{
    public function create(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): FollowRequest;
}
