<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveFollowRequest;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class RemoveFollowRequestInput implements RemoveFollowRequestInputPort
{
    public function __construct(private AccountIdentifier $requestingAccountIdentifier, private AccountIdentifier $targetAccountIdentifier) {}

    public function requestingAccountIdentifier(): AccountIdentifier
    {
        return $this->requestingAccountIdentifier;
    }

    public function targetAccountIdentifier(): AccountIdentifier
    {
        return $this->targetAccountIdentifier;
    }
}
