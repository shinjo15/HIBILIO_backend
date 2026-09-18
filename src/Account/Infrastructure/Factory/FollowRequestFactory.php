<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Factory;

use Src\Account\Domain\Entity\FollowRequest;
use Src\Account\Domain\Factory\FollowRequestFactoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class FollowRequestFactory implements FollowRequestFactoryInterface
{
    public function create(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): FollowRequest
    {
        return new FollowRequest($requestingAccountIdentifier, $targetAccountIdentifier);
    }
}
