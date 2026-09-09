<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateFollow;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CreateFollowInput implements CreateFollowInputPort
{
    public function __construct(
        private AccountIdentifier $followingAccountIdentifier,
        private AccountIdentifier $followedAccountIdentifier,
    ) {}

    public function followingAccountIdentifier(): AccountIdentifier
    {
        return $this->followingAccountIdentifier;
    }

    public function followedAccountIdentifier(): AccountIdentifier
    {
        return $this->followedAccountIdentifier;
    }
}
